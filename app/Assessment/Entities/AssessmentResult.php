<?php

declare(strict_types=1);

namespace App\Assessment\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class AssessmentResult extends BaseEntity
{
    public function __construct(
        private ?Carbon $finalizedAt,
        private array|float $scoresData,
        private float $score,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            finalizedAt: $model->finalized_at,
            scoresData: $model->scores_data ?? [],
            score: (float) $model->score,
        );
    }

    public function isFinalized(): bool
    {
        return $this->finalizedAt !== null;
    }

    public function calculateTotalScore(): float
    {
        if (! is_array($this->scoresData)) {
            return $this->score;
        }

        $total = 0.0;
        $competencies = $this->scoresData['competencies'] ?? [];
        foreach ($competencies as $competency) {
            $indicators = $competency['indicators'] ?? [];
            foreach ($indicators as $score) {
                $total += (float) $score;
            }
        }

        return $total;
    }
}
