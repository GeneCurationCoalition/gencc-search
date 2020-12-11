<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SubmitterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('submitters')->insert([
            [
                'uuid' => "GENCC_000101",
                'curie' => "GENCC:000101",
                'title' => "Ambry Genetics",
                'path_logo' => '/brand/submitters/logo_ambry.png',
                'website' => "https://www.ambrygen.com/",
                'text_descriptions' => "Ambry genetics is a CLIA-certified fee-for-service clinical testing laboratory.",
                'text_contact' => "Kelly Radtke, Coordinator<br />Phone: 949-900-5500<br />Email: ambrydata@ambrygen.com",
                'text_assertions' => "https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5655771/"
            ],
            [
                'uuid' => "GENCC_000102",
                'curie' => "GENCC:000102",
                'title' => "ClinGen",
                'path_logo' => '/brand/submitters/logo_clingen.png',
                'website' => "https://www.clinicalgenome.org",
                'text_descriptions' => "ClinGen is a National Institutes of Health (NIH)-funded resource dedicated to building a central resource that defines the clinical relevance of genes and variants for use in precision medicine and research.
",
                'text_contact' => "Marina DiStefano, Coordinator<br>Email: mdistefa@broadinstitute.org",
                'text_assertions' => "https://clinicalgenome.org/curation-activities/gene-disease-validity/training-materials/"
            ],
            [
                'uuid' => "GENCC_000103",
                'curie' => "GENCC:000103",
                'title' => "DECIPHER",
                'path_logo' => '/brand/submitters/logo_decipher.png',
                'website' => "",
                'text_descriptions' => "",
                'text_contact' => "",
                'text_assertions' => ""
            ],
            [
                'uuid' => "GENCC_000104",
                'curie' => "GENCC:000104",
                'title' => "Genomics England PanelApp",
                'path_logo' => '/brand/submitters/logo_panelapp-eng.png',
                'website' => "https://panelapp.genomicsengland.co.uk/",
                'text_descriptions' => "Genomics England's PanelApp is a knowledgebase of curated gene panels which crowdsources expert reviews for gene-disease validity assessment The gene panels are utilised by Genomics England’s genome interpretation services, support a consensus in gene content for the NHSE Genomic Medicine Service, as well as worldwide for omics analysis.",
                'text_contact' => "Catherine Snow, Coordinator<br>Email: Catherine.Snow@genomicsengland.co.uk",
                'text_assertions' => "https://panelapp.genomicsengland.co.uk/#!Guidelines"
            ],
            [
                'uuid' => "GENCC_000105",
                'curie' => "GENCC:000105",
                'title' => "Illumina",
                'path_logo' => '/brand/submitters/logo_illumina.png',
                'website' => "https://www.illumina.com/clinical/illumina_clinical_laboratory.html",
                'text_descriptions' => "The Illumina Clinical Services Laboratory is a CLIA-certified, CAP-accredited clinical laboratory which offers the TruGenome Undiagnosed Disease Test, a clinical whole-genome sequencing test for patients with a suspected rare and undiagnosed genetic disease. The lab also supports clinical programs such as the iHope Program, a philanthropic program which donates clinical genome sequencing tests to help find answers for children facing these types of diseases.",
                'text_contact' => "Alison Coffey, Coordinator<br>Phone: +44 777 3631222<br>Email: acoffey@illumina.com",
                'text_assertions' => "https://clinicalgenome.org/site/assets/files/2169/gene_curation_sop_2016_version_5_11_6_17.pdf<br>https://clinicalgenome.org/site/assets/files/2165/gene_curation_sop_version_6_aug_2018_final.pdf<br>https://www.clinicalgenome.org/site/assets/files/3975/gene-disease_validity_standard_operating_procedures_version_7-1.pdf<br>ICSL Assertion Criteria for Gene Curation PDF"
            ],
            [
                'uuid' => "GENCC_000106",
                'curie' => "GENCC:000106",
                'title' => "Invitae",
                'path_logo' => '/brand/submitters/logo_invitae.png',
                'website' => "https://www.invitae.com/",
                'text_descriptions' => "Invitae is a CLIA-certified fee-for-service clinical testing laboratory.",
                'text_contact' => "Jacke Tahiliani, Coordinator <br>Phone: 800-436-3037<br>Email: jackie.tahiliani@invitae.com",
                'text_assertions' => "https://view.publitas.com/invitae/invitaeposter_nsgc2019_curatingthehumangenome/page/1"
            ],
            [
                'uuid' => "GENCC_000107",
                'curie' => "GENCC:000107",
                'title' => "Laboratory for Molecular Medicine",
                'path_logo' => '/brand/submitters/logo_lmm.png',
                'website' => "https://personalizedmedicine.partners.org/laboratory-for-molecular-medicine/",
                'text_descriptions' => "The Laboratory for Molecular Medicine (LMM) is a CLIA-certified molecular diagnostic laboratory, operated by Partners HealthCare Personalized Medicine. The LMM is led by a group of Harvard Medical School-affiliated faculty, geneticists, clinicians, and researchers from Brigham and Women’s Hospital and Massachusetts General Hospital, Partners' founding members. Our mission is to bridge the gap between research and clinical medicine.",
                'text_contact' => "Christina Austin-Tse, Coordinator<br>Phone: 617-768-8500<br>Email: caustint@broadinstitute.org",
                'text_assertions' => "https://www.ncbi.nlm.nih.gov/pmc/articles/PMC6323417/<br>https://www.ncbi.nlm.nih.gov/pmc/articles/PMC6612528/"
            ],

            [
                'uuid' => "GENCC_000108",
                'curie' => "GENCC:000108",
                'title' => "Myriad Women’s Health",
                'path_logo' => '/brand/submitters/logo_myriad.png',
                'website' => "https://myriadwomenshealth.com/",
                'text_descriptions' => "As a CLIA-certified clinical testing laboratory, Myriad Women’s Health provides genetic screening and support for women and their families.",
                'text_contact' => "Marie Balzotti, Coordinator<br>Email: marie.balzotti@myriad.com",
                'text_assertions' => "https://onlinelibrary.wiley.com/doi/full/10.1002/humu.24033"
            ],

            [
                'uuid' => "GENCC_000109",
                'curie' => "GENCC:000109",
                'title' => "Online Mendelian Inheritance in Man (OMIM)",
                'path_logo' => '/brand/submitters/logo_omim.png',
                'website' => "https://www.omim.org/",
                'text_descriptions' => "Online Mendelian Inheritance in Man (OMIM) is a comprehensive, authoritative compendium of human genes and genetic phenotypes that is freely available and updated daily.",
                'text_contact' => "Joanna Amberger, Coordinator<br>Email: joanna@peas.welch.jhu.edu",
                'text_assertions' => ""
            ],
            [
                'uuid' => "GENCC_000110",
                'curie' => "GENCC:000110",
                'title' => "Orphanet",
                'path_logo' => '/brand/submitters/logo_orphanet.png',
                'website' => "https://www.orpha.net/",
                'text_descriptions' => "Orphanet (www.orpha.net) is a knowledge base on rare diseases and orphan drugs, bridging the fields of healthcare and research. Orphanet, a network of 38 countries, aims to increase knowledge on rare diseases so as to improve the diagnosis, care, and treatment of rare diseases. Orphanet provides a medical terminology dedicated to rare diseases, the Orphanet nomenclature of rare diseases (ORPHA code) used in healthcare and research in Europe; it is annotated with curated scientific data, including rare disease-related genes.",
                'text_contact' => "Annie Olry<br>Email: annie.olry@inserm.fr",
                'text_assertions' => ""
            ],

            [
                'uuid' => "GENCC_000111",
                'curie' => "GENCC:000111",
                'title' => "PanelApp Australia",
                'path_logo' => '/brand/submitters/logo_panelapp-aus.png',
                'website' => "https://panelapp.agha.umccr.org/",
                'text_descriptions' => "PanelApp Australia is managed by the Australian Genomics Health Alliance (Australian Genomics) and is used by Australian diagnostic laboratories, clinicians and researchers to establish and maintain consensus virtual gene panels for use in genomic analysis.",
                'text_contact' => "Zornitza Stark, Coordinator<br>Email: zornitza.stark@vcgs.org.au",
                'text_assertions' => "https://panelapp.agha.umccr.org/#!Guidelines"
            ],

            [
                'uuid' => "GENCC_000112",
                'curie' => "GENCC:000112",
                'title' => "TGMI|G2P",
                'path_logo' => '/brand/submitters/logo_tgmi-g2p.png',
                'website' => "https://www.ebi.ac.uk/gene2phenotype",
                'text_descriptions' => "G2P (Gene2Phenotype, DOI: 10.1038/s41467-019-10016-3) is an online database of gene-disease relations and system for diagnostic variant interpretation, and is a product of the Transforming Genomic Medicine Initiative (TGMI). <br>TGMI aims to improve the quality and efficiency of clinical reporting from genomic sequence information. It is a collaboration between researchers at the University of Edinburgh, EMBL European Bioinformatics Institute, University of Cambridge, University of Exeter, Imperial College London, University of Manchester, the Broad Institute, and the Wellcome Sanger Institute, and is funded by the Wellcome Trust. TGMI are building resources to improve the speed, accuracy, sensitivity and precision of information to support clinical genome interpretation. ",
                'text_contact' => "Fiona Ciunningham <br>Email: g2p-help@ebi.ac.uk",
                'text_assertions' => "https://www.ebi.ac.uk/gene2phenotype/terminology"
            ],
        ]);
    }
}
