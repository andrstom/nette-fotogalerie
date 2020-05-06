<?php
declare(strict_types = 1);
namespace App\Presenters;
use Nette;
use Nette\Application\UI\Form;
use Nette\Mail\Message;
use Nette\Mail\IMailer;

final class HomepagePresenter extends BasePresenter
{

    private $mailer;

    public function __construct(IMailer $mailer)
    {
        $this->mailer = $mailer;
    }
    
    public function renderDefault(): void {
        
        $dir = "./img/";
        $files = scandir($dir);
        rsort($files);
        foreach ($files as $k => $v) {
            
            if ($v != '.' && $v != '..' && $v != 'setup') {
                
                foreach (scandir($dir . $v . '/thumb/') as $k1 => $v1) {
                    
                    if ($v1 != '.' && $v1 != '..' && $v1 != 'thumb') {
                        $categories[$v][] = $v1;
                    }
                }
            }
        }
        
        $this->template->categories = $categories;
    }
    
    protected function createComponentCommentForm(): Form
    {
        $form = new Form;
        $form->addProtection('Vypršel časový limit, odešlete formulář znovu');
        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);
        
        $form->addText('name', 'Vaše jméno')
            ->setRequired("Nebylo uvedeno jméno.");
        
        $form->addEmail('mail', 'Email')
            ->setRequired("Nebyl uveden email.");

        $form->addTextarea('comment', 'Váš dotaz')
            ->setRequired("Nebyl uveden dotaz.")
            ->setHtmlAttribute("rows", 8);
        
        $form->addSubmit('send', 'Odeslat a počkat na odpověď.')
                ->setHtmlAttribute("class", "btn btn-default btn-lg");
        
        
        $form->onSuccess[] = [$this, 'commentFormSucceeded'];

        return $form;
    }

    public function commentFormSucceeded(Form $form, \stdClass $values): void {
        
        $mail = new Message;
        
        $mail->setFrom($values->mail)
            ->addTo('jan.icka@email.cz')
            ->setSubject('DOTAZ - janarehackova.cz')
            ->setHtmlBody("<div style='padding: 15px 15px 15px 15px; background-color: #e5eaef; border: 1px solid #003363; border-radius: 10px 10px 10px 10px;'>"
                    . "<h2><b>Ahoj čičino,</b></h2>"
                    . "<p><i>". $values->name . "</i> (" . $values->mail . ") napsal:</p>"
                    . "<p><div style='padding: 5px 5px 5px 5px; background-color: #99adc0; border: 1px solid #325b82; border-radius: 10px 10px 10px 10px;'>" . $values->comment . "</i></p>"
                    . "</div><br></div>");

        if($mail) {
            try {
                $this->mailer->send($mail);
                $this->flashMessage('Váš dotaz byl úspěšne odeslán, brzy se Vám ozvu zpět;-)', 'success');
                $this->redirect('Homepage:');
            } catch (SendException $e) {
                Debugger::log($e, 'mailexception');
                $this->flashMessage('Uuups - něco je špatně' . $e, 'success');
                $this->redirect('Homepage:');
            }
        }
    }
}
