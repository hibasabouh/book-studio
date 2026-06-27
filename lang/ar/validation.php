<?php

/*
 * Focused Arabic validation messages covering the rules this app uses.
 * Any rule not listed here falls back to the English file automatically.
 */
return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالح.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'size' => [
        'string' => 'يجب أن يكون :attribute بطول :size أحرف.',
    ],
    'min' => [
        'string' => 'يجب ألا يقل :attribute عن :min أحرف.',
    ],
    'max' => [
        'string' => 'يجب ألا يزيد :attribute عن :max أحرف.',
    ],

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'code' => 'رمز التحقق',
        'id_token' => 'رمز Google',
    ],
];
