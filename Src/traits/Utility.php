<?php

namespace Nettixcode\Framework\Traits;

use Illuminate\Support\Facades\DB;

trait Utility
{
    public static function createModal($controller, $actions, $ids, $titles, $isforms, $functionNames, ...$functionParams)
    {
        $form_open  = '';
        $form_close = '';

        if ($isforms) {
            $form_open  = '<form action="" id="' . $actions . '-form" enctype="multipart/form-data" data-id="' . $ids . '" data-parsley-validate="">';
            $form_close = '</form>';
        }

        if (method_exists($controller, $functionNames)) {
            $content_body = call_user_func_array([$controller, $functionNames], $functionParams);
        } else {
            $content_body = $functionNames;
        }

        $content = '
				<div class="modal-header">
					<h6 class="modal-title">' . $titles . '</h6>
					<button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button">
						<span aria-hidden="true">×</span>
					</button>
				</div>
				' . $form_open . '
				<div class="modal-body py-3">
					' . $content_body . '
				</div>
				<div class="modal-footer">
					<button class="btn ripple btn-primary fw-bold" type="submit" ' . ($isforms ? 'data-form="' . $actions . '-form"' : 'data-bs-dismiss="modal"') . ' id="submit" data-value="Submit">
						' . ($isforms ? 'Submit' : 'Close') . '
					</button>
				</div>
				' . $form_close;

        return $content;
    }

    public function tableList($table, $key = '', $val = '')
    {
        $query = DB::table($table);

        if ($val != '') {
            return $query->where($key, $val)->get()->toArray();
        } else {
            return $query->get()->toArray();
        }
    }

    public function satuanBarang()
    {
        $list = ['Lembar', 'Kotak', 'Buah', 'Batang', 'Rim', 'Buku', 'Meter', 'Botol', 'Paket', 'Orang / Jam', 'Orang / Bulan', 'Orang / Hari', 'Orang / Kali', 'Orang / Kegiatan', 'Bulan', 'Pak', 'Lusin', 'Pasang', 'Tube', 'Karton', 'Roll', 'Set', 'Bungkus', 'Pcs', 'Kg', 'Unit', 'Hari', 'Unit / Hari', 'Keping', 'Detik', 'Ikat'];

        return $list;
    }

    public static function ribuanToNumber($jumlah)
    {
        return preg_replace('/[.,]/', '', $jumlah);
    }

    public static function numberToRibuan($string)
    {
        return !empty($string) ? number_format($string, 0, ',', '.') : '';
    }

    public function optionSelected($variable, $value)
    {
        return ($variable == $value ? 'selected' : '');
    }
}
