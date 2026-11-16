<?php

namespace App\Http\Requests;

use App\Models\Notice;

class UpdateNoticeRequest extends StoreNoticeRequest
{
    public function authorize(): bool
    {
        $notice = $this->route('notice');

        return $notice instanceof Notice && $this->user()->can('update', $notice);
    }
}
