<?php
namespace Database\Seeders;

use App\ExpertPanel;
use Illuminate\Database\Seeder;

class ExpertPanelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $eps = [
            [
                'id'=>5,
                'name'=>'Epilepsy GCEP',
                'affiliation_id' => 10,
            ],
            [
                'id'=>6,
                'name'=>'ID-Autism GCEP',
                'affiliation_id' => 12,
            ],
            [
                'id'=>7,
                'name'=>'Hearing Loss GCEP',
                'affiliation_id' => 14,
            ],
            [
                'id'=>8,
                'name'=>'Hypertrophic Cardiomyopathy GCEP',
                'affiliation_id' => 17,
            ],
            [
                'id'=>9,
                'name'=>'Dilated Cardiomyopathy GCEP',
                'affiliation_id' => 67,
            ],
            [
                'id'=>10,
                'name'=>'Fatty Acid Oxidation Disorders GCEP',
                'affiliation_id' => 21,
            ],
            [
                'id'=>11,
                'name'=>'UNC Biocuration Core',
                'affiliation_id' => 37,
            ],
            [
                'id'=>12,
                'name'=>'Brugada Syndrome GCEP',
                'affiliation_id' => 87,
            ],
            [
                'id'=>13,
                'name'=>'Long QT Syndrome GCEP',
                'affiliation_id' => 52,
            ],
            [
                'id'=>14,
                'name'=>'FTAAD GCEP',
                'affiliation_id' => 85,
            ],
            [
                'id'=>15,
                'name'=>'Hemostasis Thrombosis GCEP',
                'affiliation_id' => 58,
            ],
            [
                'id'=>16,
                'name'=>'Breast and Ovarian Cancer GCEP',
                'affiliation_id' => 81,
            ],
            [
                'id'=>17,
                'name'=>'Colon Cancer GCEP',
                'affiliation_id' => 83,
            ],
            [
                'id'=>18,
                'name'=>'Hereditary Cancer GCEP',
                'affiliation_id' => 49,
            ],
            [
                'id'=>19,
                'name'=>'Myeloid Malignancy VCEP',
                'affiliation_id' => 65,
            ],
            [
                'id'=>20,
                'name'=>'Aminoacidopathy GCEP',
                'affiliation_id' => 23,
            ],
            [
                'id'=>21,
                'name'=>'Mitochondrial Disease GCEP',
                'affiliation_id' => 55,
            ],
            [
                'id'=>22,
                'name'=>'Monogenic Diabetes GCEP',
                'affiliation_id' => 34,
            ],
            [
                'id'=>24,
                'name'=>'RASopathy GCEP',
                'affiliation_id' => 43,
            ],
            [
                'id'=>25,
                'name'=>'Broad-Geisinger Biocuration Core',
                'affiliation_id' => 38,
            ],
            [
                'id'=>27,
                'name'=>'PTEN VCEP',
                'affiliation_id' => 26,
            ],
            [
                'id'=>28,
                'name'=>'Rett/Angelman VCEP',
                'affiliation_id' => 47,
            ],
            [
                'id'=>29,
                'name'=>'General GCEP',
                'affiliation_id' => 53,
            ],
            [
                'id'=>31,
                'name'=>'Congenital Myopathies GCEP',
                'affiliation_id' => 61,
            ],
            [
                'id'=>33,
                'name'=>'ARVC GCEP',
                'affiliation_id' => 6,
            ],
            [
                'id'=>34,
                'name'=>'Peroxisomal Disorders GCEP',
                'affiliation_id' => 95,
            ],
            [
                'id'=>37,
                'name'=>'Brain Malformations GCEP',
                'affiliation_id' => 40,
            ],
            [
                'id'=>38,
                'name'=>'Charcot-Marie-Tooth GCEP',
                'affiliation_id' => 115,
            ],
            [
                'id'=>39,
                'name'=>'Limb Girdle Muscular Dystrophy GCEP',
                'affiliation_id' => 112,
            ],
            [
                'id'=>41,
                'name'=>'Syndromic Disorders GCEP',
                'affiliation_id' => 126,
            ],
            [
                'id'=>42,
                'name'=>'Skeletal GCEP',
                'affiliation_id' => 128,
            ],
            [
                'id'=>43,
                'name'=>'Kidney Cystic and Ciliopathy GCEP',
                'affiliation_id' => 130,
            ],
            [
                'id'=>44,
                'name'=>'Pulmonary Hypertension GCEP',
                'affiliation_id' => 140,
            ],
            [
                'id'=>45,
                'name'=>'Craniofacial Malformations GCEP',
                'affiliation_id' => 125,
            ],
            [
                'id'=>46,
                'name'=>'Retina GCEP',
                'affiliation_id' => 142,
            ],
            [
                'id'=>47,
                'name'=>'AntibodyDeficiencies GCEP',
                'affiliation_id' => 157,
            ],
            [
                'id'=>48,
                'name'=>'SCID/CID GCEP',
                'affiliation_id' => 160,
            ],
            [
                'id'=>49,
                'name'=>'Parkinson\'s GCEP',
                'affiliation_id' => 155,
            ],
        ];

        foreach ($eps as $ep) {
            ExpertPanel::create($ep);
        }
    }
}
