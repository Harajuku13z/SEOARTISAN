<?php

declare(strict_types=1);

namespace App\Controllers\Public;
use App\Support\Crypto;

use App\Core\Request;
use App\Core\Response;
use App\Core\Logger;
use App\Models\Company;
use App\Models\CompanyService;
use App\Models\ConversionEvent;
use App\Models\FormSubmission;
use App\Models\Lead;
use App\Services\Mail\SmtpMailer;
use App\Services\Media\MediaUploadService;
use App\Services\Tracking\HumanSignalService;

/**
 * Handles both the quote form (POST /devis) and the contact form
 * (POST /contact). Every request is logged to form_submissions first
 * (audit/spam trail); non-spam submissions also create a `leads` row so
 * both forms feed the same admin inbox.
 */
final class FormController
{
    public function __construct(private SmtpMailer $mailer, private MediaUploadService $uploads, private HumanSignalService $humans)
    {
    }

    public function quote(Request $request): Response
    {
        return $this->handle($request, 'quote');
    }

    public function contact(Request $request): Response
    {
        return $this->handle($request, 'contact');
    }

    public function draft(Request $request): Response
    {
        $assessment=$this->humans->assess($request);if(!$assessment['is_human'])return Response::json(['ok'=>true,'filtered'=>true]);
        $name=trim((string)$request->input('name',''));$phone=trim((string)$request->input('phone',''));$email=trim((string)$request->input('email',''));
        if($name===''||$phone===''||$email===''||!filter_var($email,FILTER_VALIDATE_EMAIL))return Response::json(['ok'=>false,'message'=>'Nom, téléphone et e-mail valides sont obligatoires.'],422);
        $token=bin2hex(random_bytes(24));
        $payload=['name'=>$name,'phone'=>$phone,'email'=>$email,'address'=>trim((string)$request->input('address','')),'postal_code'=>trim((string)$request->input('postal_code','')),'city'=>trim((string)$request->input('city','')),'surface'=>trim((string)$request->input('surface','')),'source_path'=>'/simulateur-de-devis','draft_token'=>$token];
        $submission=FormSubmission::create(['form_type'=>'quote','payload'=>$payload,'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent(),'is_spam'=>false,'spam_score'=>0]);
        $lead=Lead::create(['form_submission_id'=>$submission->id(),'name'=>$name,'phone'=>$phone,'email'=>$email,'postal_code'=>$payload['postal_code']?:null,'city'=>$payload['city']?:null,'message'=>'Simulation commencée — formulaire non terminé','status'=>'abandoned']);
        return Response::json(['ok'=>true,'lead_id'=>$lead->id(),'draft_token'=>$token]);
    }

    private function handle(Request $request, string $formType): Response
    {
        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $phone = trim((string) $request->input('phone', ''));
        $message = trim((string) $request->input('message', ''));

        if ($name === '' || $phone === '' || $email === '') {
            return Response::json(['ok' => false, 'message' => 'Merci de renseigner votre nom, votre téléphone et votre e-mail.'], 422);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::json(['ok' => false, 'message' => 'Merci de saisir une adresse e-mail valide.'], 422);
        }

        $assessment = $this->humans->assess($request);
        $isSpam = !$assessment['is_human'];
        $sourceUrl = trim((string) $request->input('source_url', ''));
        if ($sourceUrl === '') {
            $sourceUrl = trim((string) $request->header('Referer', ''));
        }
        $sourcePath = (string) (parse_url($sourceUrl, PHP_URL_PATH) ?: '/');
        if (!str_starts_with($sourcePath, '/')) {
            $sourcePath = '/' . $sourcePath;
        }

        $serviceIdInput = (string) $request->input('company_service_id', '');
        $serviceId = ctype_digit($serviceIdInput) && (int) $serviceIdInput > 0 ? (int) $serviceIdInput : null;
        $projectDetails = [
            'Adresse du projet' => trim((string) $request->input('address', '')),
            'Groupes choisis' => implode(', ', (array)$request->input('service_groups', [])),
            'Sous-services choisis' => implode(', ', (array)$request->input('subservices', [])),
            'Nature du projet' => trim((string) $request->input('project_nature', '')),
            'Délai souhaité' => trim((string) $request->input('project_timing', '')),
            'Type de bien' => trim((string) $request->input('property_type', '')),
            'Statut d’occupation' => trim((string) $request->input('occupancy', '')),
            'Surface' => trim((string) $request->input('surface', '')),
            'Année de construction' => trim((string) $request->input('construction_year', '')),
            'Nombre de niveaux' => trim((string) $request->input('levels', '')),
            'Isolation' => trim((string) $request->input('insulation', '')),
            'Installation actuelle' => trim((string) $request->input('current_system', '')),
            'Âge de l’équipement' => trim((string) $request->input('equipment_age', '')),
            'Budget envisagé' => trim((string) $request->input('budget', '')),
            'Financement / aides' => trim((string) $request->input('financing', '')),
        ];
        $projectDetails = array_filter($projectDetails, static fn ($value) => $value !== '');
        $leadMessage = $message;
        if ($projectDetails !== []) {
            $leadMessage = implode("\n", array_map(static fn ($label, $value) => $label . ' : ' . $value, array_keys($projectDetails), $projectDetails))
                . ($message !== '' ? "\n\nPrécisions :\n" . $message : '');
        }
        $photoUrls=[];$files=$_FILES['project_photos']??null;
        if(is_array($files)&&is_array($files['name']??null))foreach($files['name'] as $i=>$fileName){if(($files['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)continue;try{$media=$this->uploads->store(['name'=>$fileName,'type'=>$files['type'][$i]??'','tmp_name'=>$files['tmp_name'][$i]??'','error'=>$files['error'][$i],'size'=>$files['size'][$i]??0],'other');$photoUrls[]=(string)$media->getAttribute('url');}catch(\Throwable $e){Logger::error('Photo projet refusée',['error'=>$e->getMessage()]);}}
        if($photoUrls){$projectDetails['Photos du projet']=implode(', ',$photoUrls);$leadMessage.="\n\nPhotos :\n".implode("\n",$photoUrls);}

        $payload = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'postal_code' => trim((string) $request->input('postal_code', '')),
            'city' => trim((string) $request->input('city', '')),
            'company_service_id' => $serviceId,
            'time_slot' => trim((string) $request->input('time_slot', '')),
            'message' => $message,
            'service' => trim((string) $request->input('service', '')),
            'source_url' => $sourceUrl,
            'source_path' => $sourcePath,
            'project_details' => $projectDetails,
        ];

        $submission = FormSubmission::create([
            'form_type' => $formType,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_spam' => $isSpam,
            'spam_score' => $assessment['score'],
        ]);

        if (!$isSpam) {
            $draftLeadId=(int)$request->input('draft_lead_id',0);$draftToken=(string)$request->input('draft_token','');$lead=null;
            if($draftLeadId>0&&$draftToken!==''){$candidate=Lead::find($draftLeadId);$draftSubmission=$candidate?FormSubmission::find((int)$candidate->getAttribute('form_submission_id')):null;$draftPayload=(array)($draftSubmission?->getAttribute('payload')??[]);if($candidate&&hash_equals((string)($draftPayload['draft_token']??''),$draftToken))$lead=$candidate;}
            $leadData=[
                'form_submission_id' => $submission->id(),
                'name' => $name,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
                'postal_code' => $payload['postal_code'] ?: null,
                'city' => $payload['city'] ?: null,
                'company_service_id' => $payload['company_service_id'],
                'time_slot' => $payload['time_slot'] ?: null,
                'message' => $leadMessage ?: null,
                'status' => $draftLeadId>0?'completed':'new',
            ];
            if($lead){$lead->fill($leadData);$lead->save();}else{$lead=Lead::create($leadData);}
            $this->notifyAdmin($formType, $payload, $lead->id());
            $this->confirmToCustomer($payload);
        }
        $city=trim((string)$request->header('CF-IPCity',''));$country=trim((string)$request->header('CF-IPCountry',''));
        ConversionEvent::create(['event_type'=>$isSpam?'form_attempt':'form_conversion','element_text'=>$formType==='quote'?'Demande de devis':'Formulaire de contact','target_url'=>null,'page_path'=>$sourcePath,'location_label'=>trim(implode(', ',array_filter([$city,$country])))?:null,'session_id'=>mb_substr((string)$request->input('session_id',''),0,64)?:null,'ip_hash'=>hash('sha256',$request->ip().'|'.csrf_token()),'ip_encrypted'=>Crypto::encrypt($request->ip(),(string)config('app.key','')),'user_agent'=>mb_substr($request->userAgent(),0,255),'is_human'=>!$isSpam,'bot_score'=>$assessment['score'],'rejection_reason'=>$assessment['reasons']?implode(',',$assessment['reasons']):null,'metadata'=>['form_type'=>$formType]]);

        if (!$request->wantsJson()) {
            return Response::redirect('/succes');
        }
        return Response::json(['ok' => true, 'message' => 'Votre demande a bien été envoyée.', 'redirect' => '/succes', 'conversion' => !$isSpam]);
    }

    private function notifyAdmin(string $formType, array $payload, int $leadId): void
    {
        $company = Company::current();
        $recipient = (string) ($company?->getAttribute('leads_email') ?: $company?->getAttribute('public_email') ?: config('mail.from.address', ''));
        $service = (string) ($payload['service'] ?? '');
        if ($service === '' && !empty($payload['company_service_id'])) {
            $service = (string) (CompanyService::find((int) $payload['company_service_id'])?->getAttribute('public_name') ?? '');
        }
        $safe = static fn ($value): string => htmlspecialchars((string) ($value ?: 'Non renseigné'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $rows = ['Page d’origine'=>$payload['source_path'] ?? '/','Nom'=>$payload['name'],'Téléphone'=>$payload['phone'],'E-mail'=>$payload['email'],'Service'=>$service,'Code postal'=>$payload['postal_code'],'Ville'=>$payload['city'],'Créneau souhaité'=>$payload['time_slot']];
        foreach ((array) ($payload['project_details'] ?? []) as $label => $value) {
            $rows[(string) $label] = $value;
        }
        $rows['Message'] = $payload['message'];
        $table = '';
        foreach ($rows as $label => $value) $table .= '<tr><th style="padding:10px 14px;text-align:left;background:#f3f6f8;border-bottom:1px solid #dde3e8;width:180px">'.$safe($label).'</th><td style="padding:10px 14px;border-bottom:1px solid #dde3e8">'.nl2br($safe($value)).'</td></tr>';
        $kind = $formType === 'quote' ? 'de devis' : 'de contact';
        $mailHtmlSetting = \App\Models\Setting::first(['key' => 'mail.notification_html'])?->getAttribute('value');
        if (!$mailHtmlSetting) {
            $mailHtmlSetting = '<!doctype html><html lang="fr"><body style="font-family:Arial,sans-serif;background:#f4f7f6;color:#27313a"><div style="max-width:640px;margin:30px auto;background:#fff;padding:32px"><h1>Nouvelle demande {{type}}</h1><p>Prospect n°{{lead_id}} reçu depuis le site web.</p><table style="width:100%;border-collapse:collapse">{{table}}</table><p><a href="{{admin_link}}">Voir dans l’administration</a></p><p>Cet e-mail a été envoyé automatiquement par {{company_name}}.</p></div></body></html>';
        }
        $companyName = (string) ($company?->getAttribute('trade_name') ?: config('app.name', 'Site Artisan'));
        $html = str_replace(['{{type}}', '{{lead_id}}', '{{table}}', '{{admin_link}}', '{{company_name}}'], [$kind, $leadId, $table, rtrim((string)config('app.url'), '/').'/admin/leads', $safe($companyName)], $mailHtmlSetting);
        try {
            $this->mailer->sendHtml($recipient, 'Nouvelle demande ' . $companyName . ' — ' . $payload['name'], $html, $payload['email']);
        } catch (\Throwable $e) {
            Logger::error('Échec notification e-mail formulaire', ['lead_id'=>$leadId,'error'=>$e->getMessage()]);
        }
    }

    private function confirmToCustomer(array $payload): void
    {
        $company = Company::current();
        $companyName = (string) ($company?->getAttribute('trade_name') ?: config('app.name', 'Site Artisan'));
        $phone = (string) ($company?->getAttribute('phone') ?? '');
        $name = htmlspecialchars((string)$payload['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $slotLabels = ['matin'=>'le matin','apresmidi'=>'l’après-midi','soir'=>'en soirée','urgence'=>'dès que possible','8h30-10h'=>'entre 8h30 et 10h','10h-13h'=>'entre 10h et 13h','13h-15h'=>'entre 13h et 15h','15h-18h'=>'entre 15h et 18h'];
        $slot = $slotLabels[(string)$payload['time_slot']] ?? 'selon vos disponibilités';
        $html = '<!doctype html><html><body style="margin:0;background:#f3f6f8;font-family:Arial,sans-serif;color:#172833"><div style="max-width:640px;margin:30px auto;background:#fff;border-radius:16px;overflow:hidden"><div style="padding:28px;background:#0f4c5c;color:#fff"><h1 style="margin:0;font-size:25px">Votre demande a bien été reçue</h1></div><div style="padding:28px"><p>Bonjour '.$name.',</p><p>Nous vous confirmons la bonne réception de votre demande auprès de '.$companyName.'.</p><p>Notre équipe va l’étudier et vous rappellera <strong>'.$slot.'</strong>.</p>'.($phone!==''?'<p>Si votre demande est urgente, vous pouvez nous joindre au <strong>'.htmlspecialchars($phone,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</strong>.</p>':'').'<p style="margin-top:28px">À très bientôt,<br><strong>L’équipe '.$companyName.'</strong></p></div></div></body></html>';
        $replyTo = (string) ($company?->getAttribute('public_email') ?: config('mail.from.address', ''));
        try {
            $this->mailer->sendHtml((string)$payload['email'], 'Nous avons bien reçu votre demande — ' . $companyName, $html, $replyTo);
        } catch (\Throwable $e) {
            Logger::error('Échec confirmation e-mail client', ['email'=>$payload['email'],'error'=>$e->getMessage()]);
        }
    }

}
