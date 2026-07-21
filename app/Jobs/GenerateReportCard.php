<?php

namespace App\Jobs;

use App\Models\TpqSemester;
use App\Models\TpqStudent;
use App\Services\TpqReportCardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'heavy';

    public function __construct(
        public TpqStudent $student,
        public TpqSemester $semester,
    ) {}

    public function handle(TpqReportCardService $service): void
    {
        $service->generate($this->student, $this->semester);
    }
}
