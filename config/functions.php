<?php
function nilai_label($nilai) {
    switch((int)$nilai) {
        case 4: return "BSB";
        case 3: return "BSH";
        case 2: return "MB";
        case 1: return "BB";
        default: return "-";
    }
}
function nilai_desc($nilai, $kode='') {
    $desc = [
        'C1' => [
            4 => 'Mampu melakukan kegiatan agama dan berperilaku baik secara mandiri dan konsisten.',
            3 => 'Mampu melakukan kegiatan agama dan berperilaku baik tanpa diingatkan guru.',
            2 => 'Mulai mampu melakukan kegiatan agama dan berperilaku baik, tetapi masih perlu diingatkan.',
            1 => 'Belum mampu melakukan kegiatan agama dan perilaku baik tanpa bimbingan guru.'
        ],
        'C2' => [
            4 => 'Motorik sangat baik dan mandiri. Menggambar/mewarnai dengan rapi dan gerakan tangan lancar.',
            3 => 'Motorik berkembang dengan baik. Mampu menggambar atau membuat bentuk secara mandiri.',
            2 => 'Motorik mulai berkembang. Mampu melakukan kegiatan tetapi masih perlu arahan.',
            1 => 'Motorik belum berkembang. Kesulitan melakukan kegiatan dan masih perlu bantuan.'
        ],
        'C3' => [
            4 => 'Memahami konsep dengan sangat baik. Mengenal warna, mencampurkan warna, dan menjelaskan hasilnya dengan benar.',
            3 => 'Memahami konsep dengan baik. Mengenal dan menggunakan konsep yang diberikan secara mandiri.',
            2 => 'Mulai memahami konsep. Mengenal konsep dasar tetapi masih perlu arahan.',
            1 => 'Belum memahami konsep. Belum mampu mengenali konsep tanpa bantuan guru.'
        ],
        'C4' => [
            4 => 'Kemampuan bahasa sangat baik. Menjawab pertanyaan dan menceritakan kembali dengan jelas.',
            3 => 'Kemampuan bahasa berkembang dengan baik. Mampu berbicara dan menjawab pertanyaan secara mandiri.',
            2 => 'Kemampuan bahasa mulai berkembang. Mulai mampu berbicara atau bercerita tetapi masih perlu arahan.',
            1 => 'Kemampuan bahasa belum berkembang. Masih kesulitan berbicara atau menjawab tanpa bantuan.'
        ],
        'C5' => [
            4 => 'Kemampuan sosial emosional sangat baik. Mampu bekerja sama, berbagi, membantu teman, dan mengendalikan emosi dengan baik.',
            3 => 'Kemampuan sosial emosional berkembang dengan baik. Mampu bermain bersama, berbagi, dan bekerja sama dengan teman.',
            2 => 'Kemampuan sosial emosional mulai berkembang. Mulai mau bekerja sama dan berbagi, tetapi masih perlu arahan.',
            1 => 'Kemampuan sosial emosional belum berkembang. Masih kesulitan bekerja sama, berbagi, dan mengendalikan emosi.'
        ],
        'C6' => [
            4 => 'Kemampuan seni sangat baik. Mampu melukis dengan warna yang sesuai, rapi, dan mandiri.',
            3 => 'Kemampuan seni berkembang dengan baik. Mampu membuat lukisan dengan warna yang sesuai tanpa bantuan.',
            2 => 'Kemampuan seni mulai berkembang. Mampu membuat lukisan tetapi masih perlu bimbingan.',
            1 => 'Kemampuan seni belum berkembang. Belum mampu membuat lukisan meskipun sudah dibimbing.'
        ]
    ];
    $v = (int)$nilai;
    if ($kode && isset($desc[$kode][$v])) return $desc[$kode][$v];
    return '-';
}
function nilai_class($nilai) {
    switch((int)$nilai) {
        case 4: return "text-bg-success";
        case 3: return "text-bg-primary";
        case 2: return "text-bg-warning";
        case 1: return "text-bg-danger";
        default: return "text-bg-secondary";
    }
}
?>
