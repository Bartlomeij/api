<?php

namespace AppBundle\Form;


use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateUserForm extends UserForm
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'is_edit' => true
        ]);
    }
}