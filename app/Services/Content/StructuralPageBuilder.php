<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Company;
use App\Models\Page;
use App\Models\PageBlock;

/**
 * Builds pages that don't need AI prose - contact, realizations, zones,
 * legal pages. Real company data goes straight into blocks the public
 * theme already knows how to render (form/map/projects/service_area).
 * Legal text is explicitly a template to have validated by a professional
 * (prompt.md section 5).
 */
final class StructuralPageBuilder
{
    public function build(string $pageType, string $slug, string $title, Company $company): Page
    {
        $page = Page::findBySlug($slug) ?? new Page();
        $page->fill([
            'type' => $pageType,
            'slug' => $slug,
            'title' => $title,
            'h1' => $title,
            'meta_title' => $title . ' - ' . $company->getAttribute('trade_name'),
            'meta_description' => (string) ($company->getAttribute('short_description') ?? ''),
            'status' => 'draft',
            'indexable' => true,
            'content_is_placeholder' => false,
            'last_generated_at' => date('Y-m-d H:i:s'),
        ]);
        $page->save();

        foreach (PageBlock::where(['page_id' => $page->id()]) as $block) {
            $block->delete();
        }

        $blocks = match ($pageType) {
            'contact' => $this->contactBlocks($company),
            'realizations' => [['type' => 'projects', 'data' => []]],
            'zones' => [['type' => 'service_area', 'data' => []]],
            'legal_mentions' => [['type' => 'text', 'data' => ['content' => $this->legalMentions($company)]]],
            'privacy' => [['type' => 'text', 'data' => ['content' => $this->privacyPolicy($company)]]],
            'cookies' => [['type' => 'text', 'data' => ['content' => $this->cookiesPolicy($company)]]],
            default => [],
        };

        foreach ($blocks as $position => $block) {
            PageBlock::create([
                'page_id' => $page->id(),
                'type' => $block['type'],
                'position' => $position,
                'data' => $block['data'],
                'is_active' => true,
            ]);
        }

        return $page;
    }

    /** @return array<int,array{type:string,data:array<string,mixed>}> */
    private function contactBlocks(Company $company): array
    {
        return [
            ['type' => 'text', 'data' => ['content' => 'Contactez ' . $company->getAttribute('trade_name') . ' pour toute demande.']],
            ['type' => 'form', 'data' => ['form_type' => 'contact']],
            ['type' => 'map', 'data' => ['address' => $company->getAttribute('address'), 'city' => $company->getAttribute('city')]],
        ];
    }

    private function legalMentions(Company $company): string
    {
        $name = $company->getAttribute('trade_name');
        $legal = $company->getAttribute('legal_name') ?? $name;
        $siret = $company->getAttribute('siret') ?? '[SIRET a completer]';
        $address = $company->getAttribute('address') ?? '[adresse a completer]';

        return "MODELE A FAIRE VALIDER PAR UN PROFESSIONNEL DU DROIT AVANT PUBLICATION.\n\n"
            . "Editeur du site : {$legal} ({$name})\nAdresse : {$address}\nSIRET : {$siret}\n\n"
            . "Ce site est edite par {$legal}. Pour toute question relative aux presentes mentions legales, "
            . 'contactez-nous via le formulaire de contact.';
    }

    private function privacyPolicy(Company $company): string
    {
        return "MODELE A FAIRE VALIDER PAR UN PROFESSIONNEL DU DROIT AVANT PUBLICATION.\n\n"
            . "Politique de confidentialite de " . $company->getAttribute('trade_name') . ".\n\n"
            . "Les donnees collectees via les formulaires de ce site sont utilisees uniquement pour repondre a vos demandes "
            . 'et ne sont jamais transmises a des tiers sans votre consentement. Conformement au RGPD, vous disposez d\'un '
            . "droit d'acces, de rectification et de suppression de vos donnees.";
    }

    private function cookiesPolicy(Company $company): string
    {
        return "MODELE A FAIRE VALIDER PAR UN PROFESSIONNEL DU DROIT AVANT PUBLICATION.\n\n"
            . "Politique relative aux cookies de " . $company->getAttribute('trade_name') . ".\n\n"
            . 'Ce site peut utiliser des cookies techniques necessaires a son fonctionnement.';
    }
}
