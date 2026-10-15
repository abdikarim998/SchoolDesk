<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClassRosterSeeder extends Seeder
{
    private const GRADES = [
        'Form One' => 'F1',
        'Form Two' => 'F2',
        'Form Three' => 'F3',
        'Form Four' => 'F4',
    ];

    private const SECTIONS = ['A', 'B', 'C', 'D'];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->renameLegacyClasses();

            $year = (string) Carbon::now()->year;

            $maleFirstNames = [
                'Abdi', 'Ahmed', 'Ali', 'Anwar', 'Bashir', 'Cabdi', 'Cumar', 'Daud', 'Faisal', 'Farah',
                'Hassan', 'Hamza', 'Ibrahim', 'Ismail', 'Jama', 'Khalid', 'Mahad', 'Mohamed', 'Mustafa', 'Nuur',
                'Omar', 'Osman', 'Ridwaan', 'Said', 'Samatar', 'Warsame', 'Yasin', 'Yusuf', 'Abshir', 'Aden',
            ];
            $femaleFirstNames = [
                'Aisha', 'Amina', 'Asma', 'Ayan', 'Barwaaqo', 'Deqa', 'Faadumo', 'Fardowsa', 'Hani', 'Hodan',
                'Ifrah', 'Ikram', 'Khadra', 'Layla', 'Maryan', 'Najma', 'Nasteha', 'Nimco', 'Rahma', 'Roda',
                'Sahra', 'Shamso', 'Sumaya', 'Warsan', 'Yasmin', 'Zainab', 'Sagal', 'Hawa', 'Nimo', 'Fadumo',
            ];
            $familyNames = [
                'Ali', 'Ahmed', 'Hassan', 'Abdi', 'Omar', 'Farah', 'Jama', 'Osman', 'Warsame', 'Ismail',
                'Yusuf', 'Mohamud', 'Ibrahim', 'Abdullahi', 'Aden', 'Hersi', 'Barre', 'Nuur', 'Dahir', 'Guled',
                'Samatar', 'Hashi', 'Mire', 'Mahad', 'Said', 'Bile', 'Suleiman', 'Hashim', 'Kahiye', 'Haybe',
                'Diiriye', 'Kedir', 'Adan', 'Awad', 'Roble', 'Gure', 'Jamaale', 'Keyse', 'Shire', 'Nuradin',
            ];
            $passwordHash = Hash::make('School123!');
            foreach (self::GRADES as $grade => $gradeCode) {
                foreach (self::SECTIONS as $sectionIndex => $section) {
                    $gender = in_array($section, ['A', 'B'], true) ? 'male' : 'female';
                    $classCode = $gradeCode.$section;
                    $class = SchoolClass::updateOrCreate(
                        ['name' => $grade.' '.$section],
                        [
                            'grade_level' => $grade,
                            'section' => $section,
                            'gender' => $gender,
                            'class_members' => 30,
                            'academic_year' => $year,
                        ],
                    );
                    $this->fillRoster(
                        $class,
                        $classCode,
                        $gender === 'male' ? $maleFirstNames : $femaleFirstNames,
                        $familyNames,
                        $sectionIndex + (($sectionIndex >= 2) ? 2 : 0),
                        $passwordHash,
                    );

                }
            }
        });
    }

    private function renameLegacyClasses(): void
    {
        $legacyClasses = [
            ['Form Four', 'Form Four A', 'Form Four'],
            ['Form Three', 'Form Three A', 'Form Three'],
            ['From one', 'Form One A', 'Form One'],
            ['From Two', 'Form Two A', 'Form Two'],
        ];

        foreach ($legacyClasses as [$oldName, $newName, $grade]) {
            $class = SchoolClass::where('name', $oldName)->first();

            if (! $class || SchoolClass::where('name', $newName)->exists()) {
                continue;
            }

            $class->update([
                'name' => $newName,
                'grade_level' => $grade,
                'section' => 'A',
                'gender' => 'male',
                'class_members' => 30,
                'academic_year' => (string) Carbon::now()->year,
            ]);
        }
    }

    /** @param list<string> $firstNames
     *  @param list<string> $familyNames
     */
    private function fillRoster(
        SchoolClass $class,
        string $classCode,
        array $firstNames,
        array $familyNames,
        int $surnameOffset,
        string $passwordHash,
    ): void {
        $currentCount = StudentProfile::where('class_id', $class->id)->count();

        for ($number = $currentCount + 1; $number <= 30; $number++) {
            $firstName = $firstNames[$number - 1];
            $familyName = $familyNames[($number - 1 + $surnameOffset * 5) % count($familyNames)];
            $email = sprintf('student.demo.%s.%03d@school.test', strtolower($classCode), $number);
            $admissionNumber = sprintf('DEMO-%s-%s-%03d', Carbon::now()->year, $classCode, $number);
            $student = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $firstName.' '.$familyName,
                    'role' => 'student',
                    'phone' => null,
                    'password' => $passwordHash,
                ],
            );

            StudentProfile::firstOrCreate(
                ['user_id' => $student->id],
                ['class_id' => $class->id, 'admission_number' => $admissionNumber],
            );
        }
    }
}
