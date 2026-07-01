<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CourseTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The minimum and maximum number of instructors enrolled per course.
     */
    private const int MIN_INSTRUCTORS = 1;

    private const int MAX_INSTRUCTORS = 5;

    /**
     * The minimum and maximum number of students enrolled per course.
     */
    private const int MIN_STUDENTS = 0;

    private const int MAX_STUDENTS = 100;

    /**
     * Seed a set of courses, each with a generated cover image and a random
     * roster of instructors and students.
     */
    public function run(): void
    {
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $courses = Course::factory()->count(25)->create();
        }

        $instructor_ids = $this->userIdsWithRoles([UserRole::Instructor, UserRole::Admin]);
        $student_ids = $this->userIdsWithRoles([UserRole::Student]);

        foreach ($courses as $course) {
            $this->attachCover($course);
            $this->enrollUsers($course, $instructor_ids, $student_ids);
        }
    }

    /**
     * Get the ids of users assigned any of the given roles. Unlike Spatie's
     * `role()` scope, this does not throw when a role has not been created yet.
     *
     * @param  array<int, UserRole>  $roles
     * @return Collection<int, int>
     */
    private function userIdsWithRoles(array $roles): Collection
    {
        $role_names = array_map(fn (UserRole $role) => $role->value, $roles);

        return User::whereHas('roles', function ($query) use ($role_names) {
            $query->whereIn('name', $role_names);
        })->pluck('id');
    }

    /**
     * Enroll a random roster of instructors and students onto the course.
     * Courses that already have members are left untouched.
     *
     * @param  Collection<int, int>  $instructor_ids
     * @param  Collection<int, int>  $student_ids
     */
    private function enrollUsers(Course $course, Collection $instructor_ids, Collection $student_ids): void
    {
        if ($course->users()->exists()) {
            return;
        }

        $instructor_count = min(
            fake()->numberBetween(self::MIN_INSTRUCTORS, self::MAX_INSTRUCTORS),
            $instructor_ids->count(),
        );
        $chosen_instructors = $instructor_ids->shuffle()->take($instructor_count);

        if ($chosen_instructors->isNotEmpty()) {
            $course->instructors()->attach($chosen_instructors->all(), ['is_instructor' => true]);
        }

        $student_count = min(
            fake()->numberBetween(self::MIN_STUDENTS, self::MAX_STUDENTS),
            $student_ids->count(),
        );
        $chosen_students = $student_ids->shuffle()->take($student_count);

        if ($chosen_students->isNotEmpty()) {
            $course->students()->attach($chosen_students->all(), ['is_instructor' => false]);
        }
    }

    /**
     * Generate a deterministic, colored cover image for the course and attach
     * it to the course's cover media collection. Existing covers are left alone.
     */
    private function attachCover(Course $course): void
    {
        if ($course->getFirstMedia('cover') !== null) {
            return;
        }

        $cover_path = $this->generateCover($course);

        $course->addMedia($cover_path)
            ->usingFileName("{$course->code}-cover.jpg")
            ->toMediaCollection('cover');
    }

    /**
     * Render an 800x450 cover image whose color is derived from the course code
     * and whose label is the course code. Returns the temporary file path.
     */
    private function generateCover(Course $course): string
    {
        $width = 800;
        $height = 450;

        $image = imagecreatetruecolor($width, $height);

        [$red, $green, $blue] = $this->colorForCode($course->code);
        $background = imagecolorallocate($image, $red, $green, $blue);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        $accent = imagecolorallocatealpha($image, 255, 255, 255, 110);
        imagefilledpolygon($image, [0, $height, $width, $height, $width, $height - 140], $accent);

        $this->drawLabel($image, $course->code, $width, $height);

        $temporary_path = tempnam(sys_get_temp_dir(), 'course_cover_').'.jpg';
        imagejpeg($image, $temporary_path, 85);
        imagedestroy($image);

        return $temporary_path;
    }

    /**
     * Draw the course code centered on the cover, using a TrueType font when
     * available and falling back to a built-in bitmap font otherwise.
     *
     * @param  \GdImage  $image
     */
    private function drawLabel($image, string $code, int $width, int $height): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $font_path = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

        if (function_exists('imagettftext') && file_exists($font_path)) {
            $font_size = 54;
            $box = imagettfbbox($font_size, 0, $font_path, $code);
            $text_width = $box[2] - $box[0];
            $text_height = $box[1] - $box[7];
            $x = (int) (($width - $text_width) / 2);
            $y = (int) (($height + $text_height) / 2);

            imagettftext($image, $font_size, 0, $x, $y, $white, $font_path, $code);

            return;
        }

        $font = 5;
        $x = (int) (($width - imagefontwidth($font) * strlen($code)) / 2);
        $y = (int) (($height - imagefontheight($font)) / 2);
        imagestring($image, $font, $x, $y, $code, $white);
    }

    /**
     * Derive a pleasant RGB background color deterministically from the code.
     *
     * @return array{int, int, int}
     */
    private function colorForCode(string $code): array
    {
        $hue = crc32($code) % 360;

        return $this->hslToRgb($hue / 360, 0.55, 0.45);
    }

    /**
     * Convert an HSL color (each component 0-1) to 8-bit RGB.
     *
     * @return array{int, int, int}
     */
    private function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        if ($saturation === 0.0) {
            $value = (int) round($lightness * 255);

            return [$value, $value, $value];
        }

        $q = $lightness < 0.5
            ? $lightness * (1 + $saturation)
            : $lightness + $saturation - $lightness * $saturation;
        $p = 2 * $lightness - $q;

        return [
            (int) round($this->hueToChannel($p, $q, $hue + 1 / 3) * 255),
            (int) round($this->hueToChannel($p, $q, $hue) * 255),
            (int) round($this->hueToChannel($p, $q, $hue - 1 / 3) * 255),
        ];
    }

    /**
     * Resolve a single RGB channel for the HSL conversion.
     */
    private function hueToChannel(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }

        if ($t > 1) {
            $t -= 1;
        }

        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }

        if ($t < 1 / 2) {
            return $q;
        }

        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }
}
