<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-06-01 18:34:50              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPay4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\AmazonPay4\Form\Type\Admin\ConfigType;
use Plugin\AmazonPay4\Repository\ConfigRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigController extends AbstractController
{
    protected $validator;
    protected $configRepository;

    public function __construct(
        ValidatorInterface $validator,
        ConfigRepository $configRepository
    ){
        $this->validator = $validator;
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/amazon_pay4/config", name="amazon_pay4_admin_config")
     * @Template("@AmazonPay4/admin/config.twig")
     */
    public function index(Request $request){
        $Config = $this->configRepository->get(true);
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();

//            if ($Config->getEnv() == $this->eccubeConfig['amazon_pay4']['env']['prod']) {
//
//                $prod_key = $Config->getProdKey();
//
//                $errors = $this->validator->validate($prod_key, [new Assert\NotBlank()]);
//                if ($errors->count() != 0) {
//                    foreach ($errors as $error) {
//                        $messages[] = $error->getMessage();
//                    }
//                    $form['prod_key']->addError(new FormError($messages[0]));
//                }else{
//                    if (!password_verify($prod_key, '$2y$10$m3aYrihBaIKarlrmI39tGORK4fFBC7cWoSLFy6jkMpT7IduYVsVtO')) {
//                        $form['prod_key']->addError(new FormError('本番環境切り替えキーは有効なキーではありません。'));
//                    }
//                }
//
//            }
            if ($Config->getAmazonAccountMode() == $this->eccubeConfig['amazon_pay4']['account_mode']['owned']) {

                $privateKeyPath = $this->getParameter('kernel.project_dir') . '/' . $Config->getPrivateKeyPath();

                if (mb_substr($Config->getPrivateKeyPath(), 0, 1) == '/') {
                    $form['private_key_path']->addError(new FormError('プライベートキーパスの先頭に"/"は利用できません'));
                }else{
                    if (!is_file($privateKeyPath) || file_exists($privateKeyPath) === false) {
                        $form['private_key_path']->addError(new FormError('プライベートキーファイルが見つかりません。'));
                    }
                }
            }

            if ($form->isValid()) {
                $this->entityManager->persist($Config);
                $this->entityManager->flush($Config);
                $this->addSuccess('amazon_pay4.admin.save.success', 'admin');
                return $this->redirectToRoute('amazon_pay4_admin_config');
            }
        }


        $testAccount = $this->eccubeConfig['amazon_pay4']['test_account'];
        return ['form' => $form->createView(), 'testAccount' => $testAccount];

    }
}