<?php

declare(strict_types=1);

namespace App\Actions\Certificate;

use App\Actions\Core\LogAuditAction;
use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateCertificateTemplateAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): CertificateTemplate
    {
        $validated = Validator::validate($data, [
            'name' => 'required|string|max:255',
            'layout' => 'required|in:portrait,landscape',
            'content_template' => 'required|string',
            'is_active' => 'boolean',
            'created_by' => 'required|exists:users,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $template = CertificateTemplate::create($validated);

            $this->logAudit->execute(
                action: 'certificate_template_created',
                subjectType: CertificateTemplate::class,
                subjectId: $template->id,
                module: 'Certificate',
            );

            return $template;
        });
    }
}
