<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Actions;

use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Core\Actions\BaseCommandAction;
use Illuminate\Support\Facades\Validator;

final class CreateCertificateTemplateAction extends BaseCommandAction
{
    public function execute(array $data): CertificateTemplate
    {
        $validated = Validator::validate($data, [
            'name' => 'required|string|max:255',
            'layout' => 'required|in:portrait,landscape',
            'content_template' => 'required|string',
            'is_active' => 'boolean',
            'created_by' => 'required|exists:users,id',
        ]);

        return $this->transaction(function () use ($validated) {
            $template = CertificateTemplate::create($validated);

            $this->log('certificate_template_created', $template);

            return $template;
        });
    }
}
