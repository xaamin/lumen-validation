<?php

namespace Lumen\Validation\Tests\Support;

use Lumen\Validation\BaseRequest;

class DummyBaseRequest extends BaseRequest
{
    /**
     * @inherits
     */
    protected function authorize()
    {
        return $this->request->has('make_unauthorized') ? false : parent::authorize();
    }

    /**
     * @inherits
     */
    protected function rules()
    {
        return [
            'name' => 'required',
            'age' => 'required|numeric',
        ];
    }

    /**
     * @inherits
     */
    protected function messages()
    {
        if (!$this->has('make_message')) {
            return parent::messages();
        }

        return [
            'numeric' => 'The :attribute must be a number value',
        ];
    }

    /**
     * @inherits
     */
    protected function attributes()
    {
        if (!$this->has('make_attribute')) {
            return parent::attributes();
        }

        return [
            'age' => 'oldness',
        ];
    }
}
