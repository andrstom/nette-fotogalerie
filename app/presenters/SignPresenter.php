<?php

declare(strict_types = 1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class SignPresenter extends BasePresenter
{
    /**
     * Sign-in form factory.
     */
    protected function createComponentSignInForm(): Form
    {
        $form = new Form;
        
        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);
        
        $form->addText('username', 'Login:')
                ->setRequired('Vložit jméno.');

        $form->addPassword('password', 'Heslo:')
                ->setRequired('Vložit heslo.');

        $form->addSubmit('send', 'Přihlásit');

        // call method signInFormSucceeded() on success
        $form->onSuccess[] = [$this, 'signInFormSucceeded'];
        return $form;
    }

    public function signInFormSucceeded(Form $form, \stdClass $values): void
    {
        try {
            $this->getUser()->login($values->username, $values->password);
            $this->redirect('Settings:default');
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Nesprávný Login nebo Heslo.');
        }
    }

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('Odhlášeno !!!');
        $this->redirect('Homepage:');
    }

}
