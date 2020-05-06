<?php
declare(strict_types = 1);
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;

final class SettingsPresenter extends BasePresenter
{

    public function renderDefault(): void {

        
        $dir = "./img/";
        $files = scandir($dir);
        rsort($files);
        foreach ($files as $k => $v) {
            if ($v != '.' && $v != '..') {
                //$categories[$k] = $v;
                
                foreach (scandir($dir . $v . '/thumb/') as $k1 => $v1) {
                    if ($v1 != '.' && $v1 != '..' && $v1 != 'thumb') {
                        $categories[$v][] = $v1;
                    }
                }
            }
        }
        $this->template->categories = $categories;

    }

    protected function createComponentUploadForm(): Form
    {
        $form = new Form;

        // Set Bootstrap 3 layout
        $this->makeStyleBootstrap3($form);
        
        /*$form->addText('name', 'Název obrázku')
                ->setRequired("Vložit název souboru!");*/

        $category = [
            'portrait' => 'Portrét',
            'nature' => 'Příroda',
            'people' => 'Lidé',
            'wedding'  => 'Svatba',
            'trips'  => 'Cestování',
            'setup'  => 'Nastavení',
        ];
        $form->addSelect('category', 'Kategorie', $category)
            ->setPrompt('Zvol kategorii, čumáčku;-)');
        
        $form->addMultiUpload('file', 'Obrázek')
            ->setRequired("Vyber obrázek, lásko!!!")
            ->addRule(Form::IMAGE, 'Musí být JPEG, PNG nebo GIF.')
            //->addRule(Form::MAX_LENGTH, 'Maximální počet souborů pro nahrnání je 4.', '4')
            ->addRule(Form::MAX_FILE_SIZE, 'Maximální velikost souboru je 8 MB.', 8000 * 1024);
        
        $form->addProtection('Vypršel časový limit, odešlete formulář znovu');
        $form->addSubmit('send', 'Vložit');
        $form->onSuccess[] = [$this, 'uploadFormSucceeded'];

        return $form;
    }

    public function uploadFormSucceeded(Form $form, \stdClass $values): void {
        
        $files = $values->file;
        $path = './img/'. $values->category . '/';
        
        try {
            // Multiple upload
            foreach ($files as $k => $file) {

                // kontrola jestli se jedna o obrazek a jestli se nahral dobre
                if ($file->isImage() and $file->isOk()) {
                    
                    $tmpFile = $file->getTemporaryFile();

                    // suffix
                    $suffix = strtolower(mb_substr($file->getSanitizedName(), strrpos($file->getSanitizedName(), ".")));

                    // filename without suffix
                    $file_name = basename(strtolower($file->getSanitizedName()), $suffix);

                    // Resize and thumbnail create
                    $image = Image::fromFile($tmpFile);
                    $thumb = Image::fromFile($tmpFile);

                    if($image->getWidth() > $image->getHeight()) {
                        $image->resize(1280, NULL);
                        $thumb->resize(512, NULL);
                    } else {
                        $image->resize(NULL, 1280);
                        $thumb->resize(NULL, 512);
                    }

                    // Sharping
                    $image->sharpen();
                    $thumb->sharpen();
                    
                    // Save img and thum or redirect
                    if (!file_exists($path . $file_name . $suffix) || !file_exists($path . 'thumb/' . $file_name . '_thumb' . $suffix)){

                        $image->save($path . $file_name . $suffix);
                        $thumb->save($path . 'thumb/' . $file_name . $suffix);

                    } else {

                        $this->flashMessage('Kotě, tenhle soubor (' . $file_name . ') už existuje, zkus mu dát jiný název;-)!!!', 'success');
                        $this->redirect('Settings:');

                    }
                }
            }
            
            $this->flashMessage('Hurá, máš to tam, čumáčku!!!', 'success');
            $this->redirect('Settings:');
            
        } catch(Exception $e){
            
            $this->flashMessage('Chyba při nahrávání souboru!!! - ' . $e, 'success');
            $this->redirect('Settings:');
            
        }
    }
    
    public function actionDelete(string $file): void {
        

        $dir = "./img/";
        $files = scandir($dir);
        rsort($files);
        foreach ($files as $k => $v) {
            if ($v != '.' && $v != '..') {
                
                foreach (scandir($dir . $v ) as $k1 => $v1) {
                        
                    //$categories[$v][] = $v1;
                    //dump($file.$v1);
                    //exit();
                    if($file == $v1) {

                        unlink($dir . $v . '/' . $file);
                        unlink($dir . $v . '/thumb/' . $file);

                        $this->flashMessage('Smazáno', 'success');
                        $this->redirect('Settings:');

                    }
                }
            }
        }
        $this->flashMessage('Soubor nelze smazat - neexistuje!!!', 'success');
        $this->redirect('Settings:');
    }
}