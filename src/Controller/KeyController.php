<?php
namespace MKDF\Keys\Controller;

use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Keys\Repository\MKDFKeysRepositoryInterface;
use MKDF\Keys\Form\KeyForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter;
use Zend\View\Model\ViewModel;
use Zend\Session\SessionManager;
use Zend\Session\Container;

class KeyController extends AbstractActionController
{
    private $_config;
    private $_repository;

    public function __construct(MKDFKeysRepositoryInterface $repository, MKDFCoreRepositoryInterface $core_repository, array $config)
    {
        $this->_config = $config;
        $this->_repository = $repository;
        $this->_core_repository = $core_repository;
    }
    
    public function indexAction()
    {
        $user = $this->currentUser();
        $actions = [];
        //anonymous/logged-out user will return an ID of -1
        $userId = $user->getId();
        if ($userId > 0) {
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [[ 'class' => '', 'type' => 'primary', 'icon' => 'create', 'label' => 'Create a new key', 'target' => 'key', 'params' => ['action' => 'add']]]
            ];
        }

        $keys = $this->_repository->findAllUserKeys($userId);

        $paginator = new Paginator(new Adapter\ArrayAdapter($keys));
        $page = $this->params()->fromQuery('page', 1);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        return new ViewModel([
            'message' => 'Keys ',
            'user'  => $user,
            'keys' => $paginator,
            'actions' => $actions,
            'features' => $this->accountFeatureManager()->getFeatures($userId)
        ]);
    }
    
    public function detailsAction() {
        $userId = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        $key = $this->_repository->findKey($id, $userId);
        $datasets = $this->_repository->findKeyDatasets($id, $userId);
        if ($userId > 0) {
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                    ['type'=>'warning','label'=>'Edit', 'icon' => 'edit',  'target'=> 'key', 'params'=> ['id' => $key->id, 'action' => 'edit']],
                    ['type'=>'danger','label'=>'Delete', 'icon' => 'delete', 'target'=> 'key', 'params'=> ['id' => $key->id, 'action' => 'delete-confirm']],
                ]
            ];
        }
        $message = "Key //" . $id;
        return new ViewModel([
            'message' => $message,
            'key' => $key->getProperties(),
            'datasets' => $datasets,
            'features' => $this->accountFeatureManager()->getFeatures($userId),
            'actions' => $actions
        ]);
    }

    public function addAction(){
        $user_id = $this->currentUser()->getId();
        $form = new KeyForm();
        // Check if user has submitted the form
        $messages = [];
        if($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()){
                // Write data
                $id = $this->_repository->insertKey(['name' => $data['name'], 'description'=>$data['description'],'user_id'=>$user_id]);
                // Redirect to "view" page
                $this->flashMessenger()->addSuccessMessage('A new key was created.');
                return $this->redirect()->toRoute('key', ['action'=>'index']);
            }else{
                $messages[] = [ 'type'=> 'warning', 'message'=>'Please check the content of the form.'];
            }
        }
        // Pass form variable to view
        return new ViewModel([
            'form' => $form,
            'messages' => $messages,
            'features' => $this->accountFeatureManager()->getFeatures($user_id),
        ]);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        $user_id = $this->currentUser()->getId();
        $key = $this->_repository->findKey($id, $user_id);
        $can_edit = ($key->user_id == $user_id);
        $messages = [];
        if($can_edit){
            $form = new KeyForm();
            if($this->getRequest()->isPost()) {
                $data = $this->params()->fromPost();
                $form->setData($data);
                if($form->isValid()){
                    // Get User Id
                    $user_id = $this->currentUser()->getId();
                    // Write data
                    $id = $this->_repository->updateKey($id, $data['name'], $data['description']);
                    // Redirect to "view" page
                    $this->flashMessenger()->addSuccessMessage('The key was updated successfully.');
                    return $this->redirect()->toRoute('key', ['action'=>'index']);
                }else{
                    $messages[] = [ 'type'=> 'warning', 'message'=>'Please check the content of the form.'];
                }
            } else{
                $form->setData($key->getProperties());
            }
            // Pass form variable to view
            return new ViewModel([
                'form'      => $form,
                'messages'  => $messages,
                'key'       => $key,
                'features'  => $this->accountFeatureManager()->getFeatures($user_id),
            ]);
        }else{
            // FIXME Better handling security
            throw new \Exception('Unauthorized');
        }
    }

    public function deleteConfirmAction(){
        //
        $id = (int) $this->params()->fromRoute('id', 0);
        $user_id = $this->currentUser()->getId();
        $key = $this->_repository->findKey($id, $user_id);
        $can_edit = ($key->user_id == $user_id);
        if($can_edit){
            $token = uniqid(true);
            $container = new Container('Key_Management');
            $container->delete_token = $token;
            $messages[] = [ 'type'=> 'warning', 'message' =>
                'Are you sure you want to delete this key?'];
            return new ViewModel([
                'key' => $key,
                'token' => $token,
                'messages' => $messages,
                'features'  => $this->accountFeatureManager()->getFeatures($user_id),
            ]);
        }else{
            // FIXME Better handling security
            throw new \Exception('Unauthorized');
        }
    }

    public function deleteAction(){
        $id = (int) $this->params()->fromRoute('id', 0);
        $user_id = $this->currentUser()->getId();
        $token = $this->params()->fromQuery('token', '');
        $key = $this->_repository->findKey($id, $user_id);
        if($key == null){
            throw new \Exception('Not found');
        }
        $can_edit = ($key->user_id == $user_id);
        $container = new Container('Key_Management');
        $valid_token = ($container->delete_token == $token);
        if($can_edit && $valid_token){
            $outcome = $this->_repository->deleteKey($id);
            unset($container->delete_token);
            $this->flashMessenger()->addSuccessMessage('The key was deleted successfully.');
            return $this->redirect()->toRoute('key', ['action'=>'index']);
        }else{
            // FIXME Better handling security
            throw new \Exception('Unauthorized. Delete token was ' . (($valid_token)?'valid':'invalid') . '.');
        }
    }
}
