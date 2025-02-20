<?php

namespace App\Imports;

use App\Classification;
use App\Disease;
use App\Gene;
use App\Inheritance;
use App\Submission;
use App\Submitter;
use App\Traits\ModelTransform;
use Carbon\Carbon;
use DateTime;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Term;

class SubmissionsImport implements OnEachRow, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function  __construct($submitted_run_date)
    {
        $this->submitted_run_date = $submitted_run_date;
            //dd("ddsadsad");
            //dd($submitted_run_date);
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        //dd($this->submitted_run_date);

        //dd($rowIndex);
        if($rowIndex > 12) {
            //dd($row);

            //dd($row);
            if(isset($row["submission_id"]) && isset($row["hgnc_id"]) && isset($row["disease_id"]) && isset($row["moi_id"]) && isset($row["date"])){
                //echo "\n\n\n IMPORT ROW START - " . $rowIndex . " ... \n";
                //dd($rowIndex);

                $row["disease_id"] = preg_replace('/\s+/', '', $row["disease_id"]);
                $row["hgnc_id"] = preg_replace('/\s+/', '', $row["hgnc_id"]);

                if (preg_match('(Orphanet:|Orpha:|ORPHA:|ORPHANET:)', $row["disease_id"]) === 1) {
                    $explode = explode(":", $row["disease_id"]);
                    $row["disease_id"] = "Orphanet:" . $explode[1];
                    //$query = "ORPHA:79304";
                    //dd($query);
                }

                if (is_numeric($row["disease_id"]))
                    $row["disease_id"] = 'OMIM:' . $row["disease_id"];

                $classification_missing = Classification::curie("GENCC:000000")->first();
                $classification_record = Classification::curie($row["classification_id"])->first();
                $inheritance_missing = Inheritance::curie("HP:0000005")->first();
                $inheritance_record = Inheritance::curie($row["moi_id"])->first();
                $disease_record = Disease::curie($row["disease_id"])->first();
                //dd($row["disease_id"]);
                $gene_record = Gene::curie($row['hgnc_id'])->first();
                //dd($gene_record);
                $submitter_record = Submitter::curie($row["submitter_id"])->first();

                if (!isset($gene_record)) {
                    echo "IMPORT ERROR - GENE ERROR - NOT IN HGNC ERROR -- '" . $row["hgnc_id"] . "\n";
                    echo "IMPORT MESSAGE - TRYING GENE SYSTEM -- '" . $row["hgnc_symbol"] . "\n";

                    $gene_record = Gene::where('title', '=', $row['hgnc_symbol'])->first();

                    if (!isset($gene_record)) {
                        echo "IMPORT ERROR - GENE ERROR - NOT IN SYMBOL ERROR -- '" . $row["hgnc_symbol"] . "\n";
                    }

                }

                if(isset($gene_record->title)){
                    if ($gene_record->title != $row['hgnc_symbol']) {

                        // make sure its not an alias or previous symbol before throwing an error
                        $check = Term::name($row['hgnc_symbol'])->first();

                        if ($check === null || $check->value != $gene_record->hgnc_id)
                        {
                            echo "IMPORT ERROR - GENE ERROR - GENE SYMBOLS DON'T MATCH -- HGNC: '" . $gene_record->title . "' v.s. Submitted: '" . $row["hgnc_symbol"] . "\n";
                            unset($gene_record);
                        }
                    }
                }

                if (is_numeric($row['date'])) {
                    $date_record = true;
                } elseif (DateTime::createFromFormat('Y-m-d', $row['date']) !== FALSE) {
                    $date_record = true;
                } else {
                    try {
                        $tmp = \Carbon\Carbon::parse($row['date']);
                        $date_record = true;
                    } catch (InvalidFormatException $_) {
                    echo "IMPORT ERROR - DATE ERROR - DATE NOT SET CORRECTLY -- '" . $row["date"] . "' - '" . $row["hgnc_id"] . "\n";
                    $row['date'] = "";
                    unset($date_record);
                    }
                }

                if (!isset($disease_record)) {
                    // process processMondoApi

                    if(($row['disease_id'] != "NULL")) {
                        $processMondoApi = new Disease;
                        $processMondoApi = $processMondoApi->processMondoApi($row);
                        // the disease should be in the DB... search again...
                        $disease_record = Disease::curie($row["disease_id"])->first();
                    } else {
                        echo "- - - - SKIPPED processMondoApi - disease was NULL \n";
                    }
                } else {
                    echo "OK - Disease found - Continue " . $disease_record->id . " for " . $row['disease_id'] . "\n";
                    //dd("STOP");
                }

                // CONTINUE...

                if(isset($classification_missing) && isset($classification_record) && isset($inheritance_record) && isset($inheritance_missing) && isset($gene_record) && isset($disease_record) && isset($classification_missing) && isset($submitter_record) && isset($date_record)) {

                    //$uuid = $row["submitter_id"] . "__" . $row['submission_id'];
                    $uuid = $row["submitter_id"] . "-" . $row['hgnc_id'] . "-" . $row['disease_id'] . "-" . $row['moi_id'] . "-" . $row['classification_id'];
                    $uuid = Str::of($uuid)->replace('.', '-');
                    $uuid = Str::of($uuid)->replace(':', '_');
                    //$replaced = Str::of('(+1) 501-555-1000')->replaceMatches('/[^A-Za-z0-9]++/', '_')
                    //$uuid = Str::of($uuid)->slug('_');
                    //dd($row['date']);
                    //dd($uuid);
                    if(is_numeric($row['date'])) {
                        $date = \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date']));
                    }elseif(DateTime::createFromFormat('Y-m-d' ,$row['date']) !== FALSE){
                        //dd($row['date']);
                        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $row['date']);
                        //dd($date);
                    } else {
                        try {
                            $date = \Carbon\Carbon::parse($row['date']);
                        } catch (InvalidFormatException $_) {
                            $date = "";
                        }
                       // $date = "";

                    }
                    //if(!$row['status']) {
                    //    $row['status'] = '1';
                    //}
                    //dd($row['notes'] ?? '');
                    $submission = Submission::updateOrCreate(
                        [
                        'submitted_as_hgnc_id'                   => $row['hgnc_id'] ?? '',
                        'submitted_as_disease_id'                => $row['disease_id'] ?? '',
                        'submitted_as_moi_id'                    => $row['moi_id'] ?? '',
                        'submitted_as_submitter_id'              => $row['submitter_id'] ?? '',
                        //'status'                                 => $row['status']
                        //'submitted_run_date'                     => $this->submitted_run_date,
                        ],
                        [
                        'uuid'                                   => $uuid,
                        'order'                                  => $classification_record->order,
                        'submitted_run_date'                     => $this->submitted_run_date,
                        'submitted_as_submission_id'             => $row["submission_id"] ?? '',
                        //'submitted_as_hgnc_id'                   => $row['hgnc_id'] ?? '',
                        'submitted_as_hgnc_symbol'               => $row['hgnc_symbol'] ?? '',
                        //'submitted_as_disease_id'                => $row['disease_id'] ?? '',
                        'submitted_as_disease_name'              => $row['disease_name'] ?? '',
                        //'submitted_as_moi_id'                    => $row['moi_id'] ?? '',
                        'submitted_as_moi_name'                  => $row['moi_name'] ?? '',
                        //'submitted_as_submitter_id'              => $row['submitter_id'] ?? '',
                        'submitted_as_submitter_name'            => $row['submitter_name'] ?? '',
                        'submitted_as_classification_id'         => $row['classification_id'] ?? '',
                        'submitted_as_classification_name'       => $row['classification_name'] ?? '',
                        'submitted_as_date'                      => $date,
                        'submitted_as_public_report_url'         => $row['public_report_url'] ?? '',
                        'submitted_as_notes'                     => $row['notes'] ?? '',
                        'submitted_as_pmids'                     => $row['pmids'] ?? '',
                        'submitted_as_assertion_criteria_url'    => $row['assertion_criteria_url'] ?? '',
                        'status'                                 => $row['status'] ?? '1'
                    ]);

                    //dd($row);

                    if ($row['submitter_id'] ?? '') {
                        $submission->submitter()->associate($submitter_record);
                    }

                    if ($row['hgnc_id'] ?? '') {
                        $submission->gene()->associate($gene_record);
                    }
                    //dd($submission);
                    if ($row['disease_id'] ?? '') {
                        //$submission->disease()->associate($disease_record);
                        $submission->disease_original()->associate($disease_record);

                        //dd("sdfsdfsd");
                        if (preg_match('(MONDO:)', $disease_record->curie) === 1) {

                            //dd($disease_record->curie);
                            $submission->disease()->associate($disease_record);
                        } else {

                            //dd($disease_record->curie);
                            //dd($disease_record->equivalents->first()->curie);
                            foreach ($disease_record->equivalents as $eqivs) {
                                //dd($eqivs);
                                // $relate_options[$eqivs->id] = [
                                //     'type'          => 'equiv',
                                //     'ontology'      => $eqivs->type
                                // ];
                                if($eqivs->type == "MONDO") {
                                     $submission->disease()->associate($eqivs->id);
                                     $submission->save();
                                 }
                            }
                            //dd($submission);
                            //dd($disease_record->id);
                            //dd("else");

                            //$item = $disease_record->equivalents;
                            //dd($item);
                            //$item = $item->where('type', 'MONDO')->first();
                            //dd($item);
                            //$submission->disease()->associate($item);
                            //dd($submission);
                        }
                        //
                        $relate_options[$disease_record->id] = [
                            'type'          => 'original',
                            'ontology'      => $disease_record->type
                        ];
                        foreach ($disease_record->equivalents as $eqivs) {
                            $relate_options[$eqivs->id] = [
                                'type'          => 'equiv',
                                'ontology'      => $eqivs->type
                            ];
                            // if($eqivs->type == "MONDO") {
                            //     $submission->disease()->associate($eqivs->id);
                            // }
                        }

                        $submission->diseases()->sync($relate_options, false);

                    }

                    if ($row['moi_id'] ?? '') {
                        $submission->inheritance()->associate($inheritance_record);
                    } else {
                        $inheritance_record = $inheritance_missing;
                        $submission->inheritance()->associate($inheritance_missing);
                    }

                    if($row['classification_id'] ?? '') {
                        $submission->classification()->associate($classification_record);
                    } else {
                        $classification_record = $classification_missing;
                        $submission->classification()->associate($classification_missing);
                    }




                    echo "- - - " . $submission->id . " submitted as " . $submission->submitted_as_submission_id . "  SAVED \n";


                    $submission->save();





                } else {

                    echo "\nIMPORT MESSAGE\n";
                    if (!isset($classification_record)) {
                        echo "- - - " . $row["submission_id"] . " | was skipped. Bad classification | " . $row["classification_id"] . "\n";
                    }
                    if (!isset($inheritance_record)) {
                        echo "- - - " . $row["submission_id"] . " | was skipped. Bad inheritance | " . $row["moi_id"] . "\n";
                    }
                    if (!isset($gene_record)) {
                        echo "- - - " . $row["submission_id"] . " | was skipped. Bad Gene | " . $row["hgnc_id"] . "\n";
                    }
                    if (!isset($disease_record)) {
                        echo "- - - " . $row["submission_id"] . " | was skipped. Bad disease | " . $row["disease_id"] . "\n";
                    }
                    if (!isset($submitter_record)) {
                        echo "- - - " . $row["submission_id"] . " | was skipped. Bad submitter | " . $row["submitter_id"] . " \n";
                    }
                    echo "\nIMPORT MESSAGE\n";
                }

            } else {
                echo "\n IMPORT ROW ERROR - " . $rowIndex . " | was skipped. Missing info... \n";
                // if (!isset($row["classification_id"])) {
                //     echo $rowIndex . " | was skipped. Missing classification \n";
                // }
                // if (!isset($row["moi_id"])) {
                //     echo $rowIndex . " | was skipped. Missing inheritance \n";
                // }
                // if (!isset($row["hgnc_id"])) {
                //     echo $rowIndex . " | was skipped. Missing gene \n";
                // }
                // if (!isset($row["disease_id"])) {
                //     echo $rowIndex . " | was skipped. Missing disease \n";
                // }
                // if (!isset($row["submitter_id"])) {
                //     echo $rowIndex . " | was skipped. Missing submitter \n";
                // }
            }
        }
    }

    public function headingRow(): int
    {
        return 6;
    }
}
