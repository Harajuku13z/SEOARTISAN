<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Core\Database;
use App\Models\Company;
use App\Models\Media;

/**
 * Common data every public page needs (company, main nav). Built once per
 * request by each Public controller and merged into that page's view data.
 */
final class PublicViewData
{
    public function __construct(private Database $db)
    {
    }

    /** @return array<string,mixed> */
    public function base(): array
    {
        $company = Company::current();

        $menuServices = [];
        if ($company !== null) {
            $table = $this->db->table('company_services');
            $menuServices = $this->db->select(
                "SELECT public_name, slug FROM {$table} WHERE company_id = ? AND show_in_menu = 1 AND is_active = 1 ORDER BY sort_order ASC",
                [$company->id()]
            );
        }

        return [
            'company' => $company,
            'menuServices' => $menuServices,
            'siteMenu' => MenuService::tree(),
            'logoUrl' => $this->logoUrl($company),
        ];
    }

    private function logoUrl(?Company $company): ?string
    {
        if ($company === null) return null;
        foreach (['logo_main_media_id', 'logo_dark_media_id', 'logo_light_media_id'] as $field) {
            $id = $company->getAttribute($field);
            if ($id && ($url = Media::find((int)$id)?->getAttribute('url'))) return (string)$url;
        }
        return null;
    }
}
