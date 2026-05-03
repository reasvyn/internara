
declare(strict_types=1);

namespace App\Domain\Document\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * S2 - Sustain: PDF generation engine using dompdf.
 * S3 - Scalable: Stateless utility action.
 */
class GeneratePdfAction
{
    /**
     * Generate a PDF from HTML and save it to temporary storage.
     *
     * @return string The temporary file path.
     */
    public function execute(string $html, string $filename = 'document'): string
    {
        $pdf = Pdf::loadHTML($this->wrapHtml($html));

        $tempPath = 'temp/'.Str::random(40).'.pdf';

        Storage::disk('local')->put($tempPath, $pdf->output());

        return Storage::disk('local')->path($tempPath);
    }

    /**
     * Wraps raw HTML with standard CSS for institutional documents.
     */
    protected function wrapHtml(string $html): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12pt; line-height: 1.5; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .content { text-align: justify; }
                .footer { margin-top: 50px; }
                .signature { float: right; width: 250px; text-align: center; }
                .page-break { page-break-after: always; }
            </style>
        </head>
        <body>
            $html
        </body>
        </html>
        HTML;
    }
}
