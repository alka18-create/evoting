<?php

use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create(['is_active' => true]);
    $this->actingAs($this->admin);
});

function voterXlsx(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getActiveSheet()->fromArray($rows);
    $path = tempnam(sys_get_temp_dir(), 'voter_import') . '.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'pemilih.xlsx', null, null, true);
}

it('template diunduh sebagai xlsx', function () {
    $response = $this->get(route('admin.voters.template'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
    expect($response->headers->get('content-disposition'))->toContain('template_pemilih.xlsx');
});

it('import xlsx membuat voter termasuk NIS numerik Excel', function () {
    $file = voterXlsx([
        ['student_id', 'name', 'class_name'],
        [12345, 'Budi Santoso', 'XII RPL 1'],
        ['12346', 'Siti Aminah', 'XII RPL 2'],
    ]);

    $this->post(route('admin.voters.import'), ['excel_file' => $file])
        ->assertRedirect(route('admin.voters.index'))
        ->assertSessionHas('success');

    expect(Voter::where('student_id', '12345')->exists())->toBeTrue()
        ->and(Voter::where('student_id', '12346')->exists())->toBeTrue();
});

it('import menolak header yang salah', function () {
    $file = voterXlsx([
        ['nis', 'nama', 'kelas'],
        ['12345', 'Budi Santoso', 'XII RPL 1'],
    ]);

    $this->post(route('admin.voters.import'), ['excel_file' => $file])
        ->assertSessionHasErrors('excel_file');

    expect(Voter::count())->toBe(0);
});

it('import melewati duplikat dan baris rusak', function () {
    Voter::factory()->create(['student_id' => '12345', 'is_active' => true]);

    $file = voterXlsx([
        ['student_id', 'name', 'class_name'],
        ['12345', 'Budi Santoso', 'XII RPL 1'],
        ['', 'Tanpa NIS', 'XII RPL 1'],
        ['12399', 'Anak Baru', 'XI TKJ 1'],
    ]);

    $this->post(route('admin.voters.import'), ['excel_file' => $file])
        ->assertRedirect(route('admin.voters.index'));

    expect(Voter::count())->toBe(2)
        ->and(Voter::where('student_id', '12399')->exists())->toBeTrue();
});

it('import csv lama tetap diterima', function () {
    $path = tempnam(sys_get_temp_dir(), 'voter_import') . '.csv';
    file_put_contents($path, "student_id,name,class_name\n12345,Budi Santoso,XII RPL 1\n");
    $file = new UploadedFile($path, 'pemilih.csv', 'text/csv', null, true);

    $this->post(route('admin.voters.import'), ['excel_file' => $file])
        ->assertRedirect(route('admin.voters.index'));

    expect(Voter::where('student_id', '12345')->exists())->toBeTrue();
});
