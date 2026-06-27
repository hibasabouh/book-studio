<?php

/*
 * Focused French validation messages covering the rules this app uses.
 * Any rule not listed here falls back to the English file automatically.
 */
return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'size' => [
        'string' => 'Le champ :attribute doit contenir :size caractères.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'max' => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],

    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'password' => 'mot de passe',
        'code' => 'code de vérification',
        'id_token' => 'jeton Google',
    ],
];
