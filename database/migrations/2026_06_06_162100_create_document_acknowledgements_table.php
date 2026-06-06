<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Document acknowledgements are now tracked via the activity_log table
        // with the 'document_acknowledged' event on the Document subject.
    }

    public function down(): void {}
};
