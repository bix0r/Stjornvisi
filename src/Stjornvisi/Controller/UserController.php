<?php
namespace Stjornvisi\Controller;

use ArrayObject;
use Stjornvisi\Lib\Csv;
use Stjornvisi\View\Model\CsvModel;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\Authentication\AuthenticationService;

use Stjornvisi\Form\User as UserForm;
use Stjornvisi\Form\UserGroups;
use Stjornvisi\Form\Password as PasswordForm;

/**
 * Class UserController.
 *
 * @package Stjornvisi\Controller
 */
class UserController extends AbstractActionController
{
    /**
     * Get one user.
     *
     * @return array|ViewModel
     */
    public function indexAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $groupService = $sm->get('Stjornvisi\Service\Group');

        $auth = new AuthenticationService();

        //ACCESS GRANTED
        //
        if ($auth->hasIdentity()) {
            if (($user = $userService->get($this->params()->fromRoute('id', 0)) ) != false) {
                return new ViewModel(
                    [
                    'user'=> $user,
                    'groups' => $groupService->getByUser($user->id),
                    'attendance' => $userService->attendance($user->id),
                    'access' => $userService->getTypeByUser(
                        $user->id,
                        ($auth->hasIdentity())? $auth->getIdentity()->id : null
                    ),
                    ]
                );
            } else {
                return $this->notFoundAction();
            }
            //ACCESS DENIED
            //
        } else {
            $this->getResponse()->setStatusCode(401);
            $model = new ViewModel();
            $model->setTemplate('error/401');
            return $model;
        }
    }

    /**
     * Get all attendance by user in a JSON format.
     *
     * @todo   is this used anywere?
     * @return JsonModel
     */
    public function attendanceAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $attendance = $userService->attendance($this->params('id'));

        return new JsonModel([$attendance]);
    }

    /**
     * Change type of user. User|Admin.
     *
     * @return \Zend\Http\Response
     */
    public function typeAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $auth = new AuthenticationService();
        $access = $userService->getTypeByUser(
            $this->params()->fromRoute('id'),
            ($auth->hasIdentity()) ? $auth->getIdentity()->id:null
        );
        //ACCESS GRANTED
        //
        if ($access->is_admin) {
            $userService->setType(
                $this->params()->fromRoute('id'),
                $this->params()->fromRoute('type')
            );
            $url = $this->getRequest()->getHeader('Referer')->getUri();
            return $this->redirect()->toUrl($url);
            //ACCESS DENIED
            //
        } else {
            $this->getResponse()->setStatusCode(401);
            $model = new ViewModel();
            $model->setTemplate('error/401');
            return $model;
        }
    }

    /**
     * List all users.
     *
     * @return ViewModel
     */
    public function listAction()
    {
        $auth = new AuthenticationService();

        //ACCESS GRANTED
        //
        if ($auth->hasIdentity()) {
            $sm = $this->getServiceLocator();
            $userService = $sm->get('Stjornvisi\Service\User');

            $access = $userService->getTypeByGroup(
                ( $auth->hasIdentity() ) ? $auth->getIdentity()->id : null,
                null
            );

            $orderMap = [
                'nafn'   => 'name',
                'titill' => 'title',
                'dags'   => 'created_date',
                'fyrirtaeki'   => 'company_name',
            ];

            $order = $orderMap[$this->params('order', 'nafn')];

            return new ViewModel([
                'users'=> $userService->fetchAll(true, $order),
                'admin' => $access->is_admin
            ]);
            //ACCESS DENIED
            //
        } else {
            $this->getResponse()->setStatusCode(401);
            $model = new ViewModel();
            $model->setTemplate('error/401');
            return $model;
        }
    }

    /**
     * Export all users as CSV file
     *
     * @return CsvModel|ViewModel
     */
    public function exportAction()
    {
        $auth = new AuthenticationService();
        if ($auth->hasIdentity()) {
            $type = $this->params('type', 'allir');

            $server = isset( $_SERVER['HTTP_HOST'] )
            ? "http://".$_SERVER['HTTP_HOST']
            : 'http://0.0.0.0' ;

            $sm = $this->getServiceLocator();
            $userService = $sm->get('Stjornvisi\Service\User');
            /** @var  $userService \Stjornvisi\Service\User */

            $csv = new Csv();

            switch ($this->params('type', 'allir')) {
                case 'formenn':
                    $users = $userService->fetchGroupMembers([2]);
                    break;
                case 'stjornendur':
                    $users = $userService->fetchGroupMembers([1,2]);
                    break;
                default:
                    $users = $userService->fetchAll();
                    break;
            }

            if ($this->params('type', 'allir') == 'formenn' || $this->params('type', 'allir') == 'stjornendur') {
                $csv->setHeader([
                    'Nafn',
                    'Titill',
                    'Netfang',
                    'Hópur',
                    'Fyrirtæki',
                    'Staða'
                ])->setName('notendalisti'.date('Y-m-d-h:i').'.csv');
                foreach ($users as $user) {
                    $csv->add([
                        $user->name,
                        $user->title,
                        $user->email,
                        $user->group_name,
                        $user->company_name,
                        ($user->type == 2) ? 'Formaður' : 'Stjórnandi' ,
                    ]);
                }
            } else {
                $csv->setHeader(
                    [
                        'Nafn',
                        'Netfang',
                        'Fyrirtæki',
                        'Lykilstarfsmaður fyrirtækis',
                        'Stofna',
                        'Seinast innskráðu(ur)',
                        'Tíðni innskráninga',
                        'Kerfisstjóri',
                        'Slóð'
                    ]
                )->setName('notendalisti'.date('Y-m-d-h:i').'.csv');

                foreach ($users as $user) {
                    $csv->add(
                        [
                            $user->name,
                            $user->email,
                            $user->company_name,
                            ($user->key_user)?'já':'nei',
                            $user->created_date->format('Y-m-d'),
                            $user->modified_date->format('Y-m-d'),
                            $user->frequency,
                            ( $user->is_admin )?'já':'nei',
                            $server . $this->url()->fromRoute('notandi/index', ['id'=>$user->id])
                        ]
                    );
                }
            }

            $model = new CsvModel();
            $model->setData($csv);
            return $model;

        } else {
            $this->getResponse()->setStatusCode(401);
            $model = new ViewModel();
            $model->setTemplate('error/401');
            return $model;
        }
    }

    /**
     * Update user's properties.
     *
     * @return \Zend\Http\Response|ViewModel
     */
    public function updateAction()
    {

        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        /** @var  $userService \Stjornvisi\Service\User */
        $companyService = $sm->get('Stjornvisi\Service\Company');
        $valuesService = $sm->get('Stjornvisi\Service\Values');

        $auth = new AuthenticationService();

        //USER FOUND
        //  user found
        if (($user = $userService->get($this->params()->fromRoute('id')) ) != false) {
            $access = $userService->getTypeByUser(
                $user->id,
                ($auth->hasIdentity())?$auth->getIdentity()->id:null
            );

            //ACCESS GRANTED
            //
            if ($access->is_admin || $access->type == 1) {
                $form = new UserForm($companyService->fetchAll(), $valuesService->getTitles());
                $form->setAttribute('action', $this->url()->fromRoute('notandi/update', ['id'=>$user->id]));

                //POST
                //  post request
                if ($this->request->isPost()) {
                    $form->setData($this->request->getPost());
                    //VALID FORM
                    //
                    if ($form->isValid()) {
                        $data = $form->getData();
                        $data['company_id'] = $data['company'];
                        unset($data['submit']);
                        unset($data['company']);
                        $userService->update($user->id, $data);
                        return $this->redirect()->toRoute('notandi/index', ['id'=>$user->id]);
                        //INVALID
                        //
                    } else {
                        $this->getResponse()->setStatusCode(400);
                        return new ViewModel(['form' => $form, 'user' => $user]);
                    }
                    //QUERY
                    //  get request
                } else {
                    $form->bind(new ArrayObject($user));
                    return new ViewModel(['form' => $form, 'user' => $user]);
                }

                //ACCESS DENIED
                //
            } else {
                $this->getResponse()->setStatusCode(401);
                $model = new ViewModel();
                $model->setTemplate('error/401');
                return $model;
            }


        //USER NOT FOUND
        //  404
        } else {
            return $this->notFoundAction();
        }
    }

    /**
     * Change user's password.
     *
     * @return \Zend\Http\Response|ViewModel
     * @todo   url for form
     */
    public function changePasswordAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');

        //USER FOUND
        //  user found in storage
        if (($user = $userService->get($this->params()->fromRoute('id', 0))) != false) {
            $auth = new AuthenticationService();
            $access = $userService->getTypeByUser(
                $user->id,
                ( $auth->hasIdentity() )? $auth->getIdentity()->id : null
            );

            //ACCESS GRANTED
            //  granted
            if ($access->is_admin || $access->type == 1) {
                $form = new PasswordForm();
                $form->setAttribute('action', '');
                if ($this->request->isPost()) {
                    $form->setData($this->request->getPost());

                    //VALID
                    //  valid form
                    if ($form->isValid()) {
                        //PASS THE SAME
                        //  both input element contain the same string
                        if ($form->get('password')->getValue() == $form->get('password-again')->getValue()) {
                            $userService->setPassword($user->id, $form->get('password')->getValue());
                            return $this->redirect()->toRoute('notandi/index', ['id'=>$user->id]);
                            //PASS MISMATCH
                            //
                        } else {
                            $form->get('password')->setMessages(['Lykilorð passa ekki saman']);
                            return new ViewModel(['form' => $form]);
                        }
                        //INVALID
                        //  invalid form
                    } else {
                        //
                    }
                } else {
                    return new ViewModel(['form' => $form]);
                }

                //ACCESS DENIED
                //  no access
            } else {
                //
            }

            //USER NOT FOUND
        } else {
            return $this->notFoundAction();
        }
    }

    /**
     * Get all groups by user.
     *
     * Allow user to update which groups he/she wants to
     * be notified about.
     *
     * @return ViewModel
     */
    public function groupsAction()
    {
        $auth = new AuthenticationService();

        $sm = $this->getServiceLocator();
        $groupService = $sm->get('Stjornvisi\Service\Group');
        /** @var $groupService \Stjornvisi\Service\Group */

        //FORM AND GROUPS
        //  get all groups user is connected to and
        //  feed that into th form
        $groups = $groupService->userConnections($auth->getIdentity()->id);
        $form = new UserGroups($groups);

        //POST
        //  request is post
        if ($this->getRequest()->isPost()) {
            $form->setData($this->request->getPost());

            //VALID
            //  form is valid, update service
            if ($form->isValid()) {
                $value = $form->getData();
                $groupService->notifyUser($value['groups'], $auth->getIdentity()->id);

                return new ViewModel(['message' => true, 'form' => $form]);
                //INVALID
                //  form is invalid
            } else {
                return new ViewModel(['message' => false, 'form' => $form]);
            }

            //QUERY
            //  request is get
        } else {
            //BIND GROUPS
            //  bind only groups user wants to be notified
            //  about
            $form->bind(new \ArrayObject(['groups'=> $groupService->fetchNotifyUser($auth->getIdentity()->id)]));

            return new ViewModel(['message' => false, 'form' => $form]);
        }
    }

    /**
     * Delete user.
     */
    public function deleteAction()
    {
        $auth = new AuthenticationService();

        if (!$auth->hasIdentity()) {
            $this->getResponse()->setStatusCode(401);
            $model = new ViewModel();
            $model->setTemplate('error/401');
            return $model;
        }

        $sm = $this->getServiceLocator();
        /** @var  $userService \Stjornvisi\Service\User */
        $userService = $sm->get('Stjornvisi\Service\User');

        if (($user = $userService->get($this->params('id', null))) != false) {
            $isSameUser = ($user->id == $auth->getIdentity()->id);

            if ($isSameUser || $auth->getIdentity()->is_admin) {
                $userService->delete($user->id);
                if ($isSameUser) {
                    $this->redirect()->toRoute('auth-out');
                } else {
                    $this->redirect()->toRoute('notandi');
                }
            } else {
                $this->getResponse()->setStatusCode(401);
                $model = new ViewModel();
                $model->setTemplate('error/401');
                return $model;
            }
        } else {
            return $this->notFoundAction();
        }
    }
}
