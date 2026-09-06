<?php

namespace App\Console\Commands;

use App\Models\NcsbQuestion;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportNcsbQuestions extends Command
{
    protected $signature = 'ncsb:import-questions {file}';

    protected $description = 'Import questions from the official NCSB v1.1 workbook';

    public function handle(): int
    {
        $sheet = IOFactory::load($this->argument('file'))
            ->getSheetByName('CS Baseline Questionnaires');
        if (! $sheet) {
            $this->error('The NCSB questionnaire sheet was not found.');

            return self::FAILURE;
        } $domain = $category = $element = '';
        $elementNumber = 0;
        for ($row = 3; $row <= 127; $row++) {
            $number = $sheet->getCell("B{$row}")->getValue();
            if (! is_numeric($number)) {
                continue;
            } $domain = trim((string) ($sheet->getCell("C{$row}")->getValue() ?: $domain));
            $category = trim((string) ($sheet->getCell("D{$row}")->getValue() ?: $category));
            $candidate = trim((string) $sheet->getCell("E{$row}")->getValue());
            if ($candidate !== '') {
                $element = $candidate;
                $elementNumber++;
            } $question = trim((string) $sheet->getCell("G{$row}")->getValue());
            if ($question === '') {
                $this->error("Question text missing at row {$row}.");

                return self::FAILURE;
            }

            NcsbQuestion::updateOrCreate(
                ['number' => (int) $number],
                [
                    'domain' => $domain,
                    'category' => $category,
                    'element_number' => $elementNumber,
                    'element_name' => $element,
                    'question' => $question,
                ]
            );
        }

        $this->info('Imported '.NcsbQuestion::count().' NCSB questions.');

        return self::SUCCESS;
    }
}
