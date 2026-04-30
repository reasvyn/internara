<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Assessment\Http\Controllers\AssessmentPdfController;

Route::middleware(['auth'])->group(function () {
    Route::get('/assessment/certificate/{registration}', [
        AssessmentPdfController::class,
        'certificate',
    ])->name('assessment.certificate');
    Route::get('/assessment/transcript/{registration}', [
        AssessmentPdfController::class,
        'transcript',
    ])->name('assessment.transcript');
});

// Public Signed Route for Verification
Route::get('/assessment/verify/{registration}', [AssessmentPdfController::class, 'verify'])
    ->name('assessment.verify')
    ->middleware('signed');
