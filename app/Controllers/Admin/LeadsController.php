<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\FormSubmission;

final class LeadsController extends AdminController
{
    public function index(Request $request): Response
    {
        $status = (string) $request->input('status', '');
        $leads = $status !== '' ? Lead::where(['status' => $status], 'created_at DESC') : Lead::all('created_at DESC');
        $leadSources = [];
        foreach ($leads as $lead) {
            $submission = FormSubmission::find((int) $lead->getAttribute('form_submission_id'));
            $payload = (array) ($submission?->getAttribute('payload') ?? []);
            $leadSources[(int) $lead->id()] = (string) ($payload['source_path'] ?? '/');
        }

        return $this->render('admin.leads.index', [
            'leads' => $leads,
            'statuses' => Lead::STATUSES,
            'currentStatus' => $status,
            'leadSources' => $leadSources,
        ], 'leads');
    }

    public function show(Request $request, array $params): Response
    {
        $lead = Lead::find((int) $params['id']);
        if ($lead === null) {
            return Response::redirect('/admin/leads');
        }

        $submission = FormSubmission::find((int) $lead->getAttribute('form_submission_id'));
        $submissionPayload = (array) ($submission?->getAttribute('payload') ?? []);

        return $this->render('admin.leads.show', [
            'lead' => $lead,
            'notes' => LeadNote::forLead((int) $lead->id()),
            'statuses' => Lead::STATUSES,
            'sourcePath' => (string) ($submissionPayload['source_path'] ?? '/'),
        ], 'leads');
    }

    public function updateStatus(Request $request, array $params): Response
    {
        $lead = Lead::find((int) $params['id']);
        if ($lead === null) {
            return Response::redirect('/admin/leads');
        }

        $status = (string) $request->input('status', '');
        if (in_array($status, Lead::STATUSES, true)) {
            $lead->setAttribute('status', $status);
            $lead->save();
            $this->log('lead.status_update', 'Lead', $lead->id(), "Statut : {$status}");
        }

        return Response::redirect('/admin/leads/' . $lead->id());
    }

    public function addNote(Request $request, array $params): Response
    {
        $lead = Lead::find((int) $params['id']);
        if ($lead === null) {
            return Response::redirect('/admin/leads');
        }

        $note = trim((string) $request->input('note', ''));
        if ($note !== '') {
            LeadNote::create([
                'lead_id' => $lead->id(),
                'author_id' => $this->auth->user()?->id(),
                'note' => $note,
            ]);
        }

        return Response::redirect('/admin/leads/' . $lead->id());
    }

    public function exportCsv(Request $request): Response
    {
        $leads = Lead::all('created_at DESC');

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['ID', 'Nom', 'Telephone', 'Email', 'Page origine', 'Ville', 'Code postal', 'Statut', 'Message', 'Date'], ',', '"', '\\');
        foreach ($leads as $lead) {
            $submission = FormSubmission::find((int) $lead->getAttribute('form_submission_id'));
            $payload = (array) ($submission?->getAttribute('payload') ?? []);
            fputcsv($handle, [
                $lead->id(),
                $lead->getAttribute('name'),
                $lead->getAttribute('phone'),
                $lead->getAttribute('email'),
                $payload['source_path'] ?? '/',
                $lead->getAttribute('city'),
                $lead->getAttribute('postal_code'),
                $lead->getAttribute('status'),
                $lead->getAttribute('message'),
                $lead->getAttribute('created_at'),
            ], ',', '"', '\\');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (new Response((string) $csv, 200))
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="demandes.csv"');
    }
}
