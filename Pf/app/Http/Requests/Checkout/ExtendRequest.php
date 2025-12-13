<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;

class ExtendRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $allowedExts = array('jpg', 'png', 'jpeg');
        return [
            'price' => 'required',
            'package_id' => 'required',
            'start_date' => 'required',
            'expire_date' => 'required',
            'payment_method' => $this->price != 0 ? 'required' : '',
            // 'receipt' => $this->is_receipt == 1 ? 'required | mimes:jpeg,jpg,png' : '',
            'cardNumber' => 'sometimes|required',
            'month' => 'sometimes|required',
            'year' => 'sometimes|required',
            'cardCVC' => 'sometimes|required',
            'receipt' => $this->is_receipt == 1 ? [
                'required', // Ensure receipt is required
                function ($attribute, $value, $fail) use ($allowedExts) {
                    if ($this->hasFile('receipt')) {
                        $img = $this->file('receipt');
                        $ext = $img->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            Session::flash('receipt_error', __('Only') . ' ' . implode(', ', $allowedExts) . ' ' . __('images are allowed'));
                            return $fail(__('Only') . ' ' . implode(', ', $allowedExts) . ' ' . __('images are allowed'));
                        }
                    } else {
                        Session::flash('receipt_error', __('receipt'));
                        return $fail(__('receipt'));
                    }
                },
            ] : '',
        ];

    }


}
