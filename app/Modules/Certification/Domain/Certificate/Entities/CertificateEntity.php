<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Entities;

use App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus;
use App\Modules\Core\Entities\BaseEntity;
use App\Modules\Core\Exceptions\RejectedException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Business rules for an issued certificate: the serial format, the verification
 * digest, and the uniqueness invariant evaluated before any write (FR-CERT-006).
 *
 * The entity never queries. The caller passes the serials and hashes already on
 * record, the entity decides whether the proposed pair may be written, and the
 * database unique indexes remain the concurrency backstop.
 */
final readonly class CertificateEntity extends BaseEntity
{
    private const SERIAL_PREFIX_LENGTH = 6;

    private const SERIAL_SEQUENCE_LENGTH = 4;

    private const FALLBACK_PREFIX = 'PKL';

    public function __construct(
        private string $id,
        private string $certificateNumber,
        private string $qrHash,
        private CertificateStatus $status,
        private ?string $registrationId,
        private ?Carbon $issuedAt,
        private ?Carbon $revokedAt,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            id: (string) $model->id,
            certificateNumber: (string) $model->certificate_number,
            qrHash: (string) $model->qr_hash,
            status: $model->status instanceof CertificateStatus
                ? $model->status
                : CertificateStatus::from((string) $model->status),
            registrationId: $model->registration_id !== null ? (string) $model->registration_id : null,
            issuedAt: $model->issued_at,
            revokedAt: $model->revoked_at,
        );
    }

    /**
     * A certificate that does not exist yet — the invariant carrier used before
     * the insert.
     */
    public static function forIssuance(string $certificateNumber, string $qrHash): self
    {
        return new self(
            id: '',
            certificateNumber: $certificateNumber,
            qrHash: $qrHash,
            status: CertificateStatus::ISSUED,
            registrationId: null,
            issuedAt: null,
            revokedAt: null,
        );
    }

    /**
     * Serial format `<PREFIX>/<YEAR>/<0001>` — the prefix is the first six
     * alphanumerics of the internship name, upper-cased, with `PKL` as fallback.
     */
    public static function composeSerial(string $internshipName, int $year, int $sequence): string
    {
        return self::composePrefix($internshipName)
            .'/'.$year
            .'/'.str_pad((string) $sequence, self::SERIAL_SEQUENCE_LENGTH, '0', STR_PAD_LEFT);
    }

    public static function composePrefix(string $internshipName): string
    {
        $letters = preg_replace('/[^A-Z0-9]/', '', strtoupper($internshipName));

        if ($letters === null || $letters === '') {
            return self::FALLBACK_PREFIX;
        }

        return substr($letters, 0, self::SERIAL_PREFIX_LENGTH);
    }

    /**
     * Deterministic fingerprint of the issuance facts. Not a signature: without
     * the stored row the verifier cannot recompute it (DD-CERT-002).
     */
    public static function composeQrHash(
        string $studentId,
        string $schoolName,
        string $score,
        string $issuerId,
        string $certificateNumber,
    ): string {
        return hash('sha256', implode('|', [
            $studentId,
            $schoolName,
            $score,
            $issuerId,
            $certificateNumber,
        ]));
    }

    /**
     * FR-CERT-006 — refuse a serial or hash that is already on record, so the
     * failure is a named business rejection instead of a constraint exception.
     *
     * @param list<string> $takenNumbers
     * @param list<string> $takenHashes
     */
    public function assertUniqueAgainst(array $takenNumbers, array $takenHashes): void
    {
        if (in_array($this->certificateNumber, $takenNumbers, true)) {
            throw new RejectedException(
                __('certificate.serial_taken', ['number' => $this->certificateNumber]),
            );
        }

        if (in_array($this->qrHash, $takenHashes, true)) {
            throw new RejectedException(__('certificate.hash_taken'));
        }
    }

    public function isRevocable(): bool
    {
        return $this->status === CertificateStatus::ISSUED;
    }

    public function belongsToRegistration(string $registrationId): bool
    {
        return $this->registrationId === $registrationId;
    }

    /**
     * Audit payload for the issuance log entry (FR-CERT-008/023).
     *
     * @return array<string, string|null>
     */
    public function auditMetadata(): array
    {
        return [
            'certificate_number' => $this->certificateNumber,
            'registration_id' => $this->registrationId,
        ];
    }

    public function id(): string
    {
        return $this->id;
    }

    public function certificateNumber(): string
    {
        return $this->certificateNumber;
    }

    public function qrHash(): string
    {
        return $this->qrHash;
    }

    public function status(): CertificateStatus
    {
        return $this->status;
    }

    public function issuedAt(): ?Carbon
    {
        return $this->issuedAt;
    }
}
