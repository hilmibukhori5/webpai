<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Services\ThresholdService;
use Illuminate\Console\Command;

class ThresholdsRecompute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thresholds:recompute {course? : Kode course (mis. MAA62043). Kosongkan untuk semua course.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute course_thresholds (PERCENTILE.INC NA per course, P dari pai_modules.percentile)';

    public function handle(ThresholdService $thresholds): int
    {
        $courseCode = $this->argument('course');

        if ($courseCode) {
            $course = Course::where('code', $courseCode)->first();

            if (! $course) {
                $this->error("Course dengan code \"{$courseCode}\" tidak ditemukan.");

                return self::FAILURE;
            }

            $result = $thresholds->recomputeForCourse($course);

            if ($result === null) {
                $this->warn("Course {$course->code} dilewati (belum punya nilai/modul/percentile).");

                return self::SUCCESS;
            }

            $this->info("Course {$course->code}: threshold_na = {$result->threshold_na} (percentile {$result->percentile}).");

            return self::SUCCESS;
        }

        $count = $thresholds->recomputeAll();

        $this->info("Selesai. {$count} course berhasil di-recompute.");

        return self::SUCCESS;
    }
}
