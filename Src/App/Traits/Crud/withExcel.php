<?php

namespace Nettixcode\App\Traits\Crud;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

trait withExcel
{
    protected static function createFromExcel($data, $insert)
    {
        $instance = new static();
        $table    = $instance->getTable();
        $json     = [];
        if (isset($data['file_name'])) {
            $upload_dir = env('UPLOAD_DIR', 'uploads/');
            $fileName   = $data['file_name'];
            $target     = $upload_dir . $fileName;
            try {
                $inputFileType = IOFactory::identify($target);
                $reader        = IOFactory::createReader($inputFileType);
                $spreadsheet   = $reader->load($target);
                $worksheet     = $spreadsheet->getActiveSheet();

                $row_counts = $worksheet->getHighestRow();

                $totalRows    = 0;
                $existingRows = 0;
                $insertedRows = 0;

                $connection = DB::connection();
                $connection->beginTransaction();

                for ($i = 2; $i <= $row_counts; $i++) {
                    $totalRows++;
                    $row_data = [];
                    foreach ($insert as $key => $columnIndex) {
                        $columnLetter   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                        $cellValue      = $worksheet->getCell($columnLetter . $i)->getValue();
                        $row_data[$key] = self::strsecure($cellValue);
                    }

                    // Cek apakah data sudah ada
                    $existingDataCount = DB::table($table)
                        ->where($row_data)
                        ->count();

                    if ($existingDataCount > 0) {
                        $existingRows++;
                        continue; // Skip if data already exists
                    } else {
                        $insertResult = DB::table($table)->insert($row_data);

                        if ($insertResult) {
                            $insertedRows++;
                        } else {
                            $connection->rollBack();

                            return response()->json([
                                'report'  => 'error',
                                'message' => 'Insert failed',
                            ]);
                        }
                    }
                }

                $connection->commit();

                if ($totalRows == $existingRows) {
                    $json = [
                        'report'  => 'existed',
                        'message' => 'Duplicated .. all data already exist!',
                    ];
                } else {
                    $json = [
                        'report'        => 'success',
                        'inserted_rows' => $insertedRows,
                        'existing_rows' => $existingRows,
                    ];
                }
            } catch (\Exception $e) {
                if (isset($connection)) {
                    $connection->rollBack();
                }
                debug_send('exceptions',$e);

                return response()->json([
                    'report'  => 'error',
                    'message' => $e->getMessage(),
                ]);
            } finally {
                debug_send('messages',$json);
                response()->json($json);
            }
        } else {
            return response()->json([
                'report'  => 'error',
                'message' => 'File not readable, please try again',
            ]);
        }
    }
}
