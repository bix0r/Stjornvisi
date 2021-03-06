<?php
namespace Stjornvisi\Controller;

use ArrayObject;
use Stjornvisi\Lib\Csv;
use Stjornvisi\View\Model\CsvModel;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Authentication\AuthenticationService;

/**
 * Class CompanyController.
 *
 * @package Stjornvisi\Controller
 */
class CompanyController extends AbstractActionController
{
    /**
     * Display one company entry.
     *
     * @return array|ViewModel
     */
    public function indexAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $companyService = $sm->get('Stjornvisi\Service\Company');
        /** @var $companyService \Stjornvisi\Service\Company */
        $groupService = $sm->get('Stjornvisi\Service\Group');
        /** @var $groupService \Stjornvisi\Service\Group */

        //COMPANY FOUND
        //
        if (($company = $companyService->get($this->params()->fromRoute('id', 0))) != false) {
            $authService = new AuthenticationService();
            $access = $userService->getTypeByCompany(
                ($authService->hasIdentity()) ? $authService->getIdentity()->id : null,
                $company->id
            );

            //ACCESS GRANTED
            //
            if ($access->is_admin || $access->type != null) {
                //CURRENT RANGE
                $currentFrom = (date('n') < 9)
                    ? new \DateTime(((int)date('Y')-1) . "-09-01")
                    : new \DateTime((date('Y')). '-09-01');
                $currentTo = (date('n') < 9)
                    ? new \DateTime(((int)date('Y')) . "-08-31")
                    : new \DateTime(((int)date('Y')+1) . "-08-31");

                $lastFrom = new \DateTime($currentFrom->format('Y-m-d'));
                $lastFrom->sub(new \DateInterval('P1Y'));
                $lastTo = new \DateTime($currentTo->format('Y-m-d'));
                $lastTo->sub(new \DateInterval('P1Y'));

                return new ViewModel(
                    [
                    'attendance' => [
                    (object)[
                    'from' => $currentFrom,
                    'to' => $currentTo,
                    'list' => $companyService->fetchEmployeesTimeRange($company->id, $currentFrom, $currentTo),
                    ],
                    (object)[
                    'from' => $lastFrom,
                    'to' => $lastTo,
                    'list' => $companyService->fetchEmployeesTimeRange($company->id, $lastFrom, $lastTo),
                    ],

                    ],
                    'distribution' => $groupService->fetchCompanyEmployeeCount($company->id),
                    'company' => $company,
                    'access' => $access
                    ]
                );
            //ACCESS DENIED
            } else {
                $this->getResponse()->setStatusCode(401);
                $model = new ViewModel();
                $model->setTemplate('error/401');
                return $model;
            }
        //COMPANY NOT FOUND
        //  404
        } else {
            return $this->notFoundAction();
        }
    }

    /**
     * Get all companies that are not "einstaklingur".
     *
     * @return ViewModel
     */
    public function listAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $companyService = $sm->get('Stjornvisi\Service\Company');

        $authService = new AuthenticationService();
        $access = $userService->getTypeByGroup(
            ($authService->hasIdentity()) ? $authService->getIdentity()->id : null,
            null
        );

        return new ViewModel(
            [
            'companies' => ($access->is_admin)
            ? $companyService->fetchAll([], $this->params('order', 'nafn'))
            : $companyService->fetchAll(['einstaklingur'], $this->params('order', 'nafn')),
            'access' => $access
            ]
        );

    }

    /**
     *Set the role of employee at a company
     *
     * @return \Zend\Http\Response
     */
    public function setRoleAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $companyService = $sm->get('Stjornvisi\Service\Company');

        //COMPANY FOUND
        //
        if (($company = $companyService->get($this->params()->fromRoute('id', 0))) != false) {
            $authService = new AuthenticationService();
            $access = $userService->getTypeByCompany(
                ($authService->hasIdentity()) ? $authService->getIdentity()->id : null,
                $company->id
            );

            //ACCESS GRANTED
            //  access granted
            if ($access->is_admin || $access->type != null) {
                $companyService->setEmployeeRole(
                    $company->id,
                    $this->params()->fromRoute('user', 0),
                    $this->params()->fromRoute('type', 0)
                );
                $url = $this->getRequest()->getHeader('Referer')->getUri();
                return $this->redirect()->toUrl($url);
                //ACCESS DENIED
                //  access denied
            } else {
                $this->getResponse()->setStatusCode(401);
                $model = new ViewModel();
                $model->setTemplate('error/401');
                return $model;
            }

        //COMPANY NOT FOUND
        //  404
        } else {
            return $this->notFoundAction();
        }
    }

    /**
     * Create company.
     *
     * @return \Zend\Http\Response|ViewModel
     */
    public function createAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $companyService = $sm->get('Stjornvisi\Service\Company');

        $authService = new AuthenticationService();
        $access = $userService->getTypeByCompany(
            ($authService->hasIdentity()) ? $authService->getIdentity()->id : null,
            null
        );

        //ACCESS GRANTED
        //	only admin can create company
        if ($access->is_admin) {
            $form = $sm->get('Stjornvisi\Form\Company');

            $form->setAttribute('action', $this->url()->fromRoute('fyrirtaeki/create'));
            //POST
            //  http post request
            if ($this->request->isPost()) {
                $form->setData($this->request->getPost());
                if ($form->isValid()) {
                    $data = $form->getData();
                    unset($data['submit']);
                    $id = $companyService->create($data);
                    $companyService->addUser($id, $authService->getIdentity()->id, 1);
                    return $this->redirect()->toRoute('fyrirtaeki/index', ['id'=>$id]);
                } else {
                    $this->getResponse()->setStatusCode(400);
                    return new ViewModel(['access' => $access, 'form' => $form]);
                }
                //QUERY
                //  http get request
            } else {
                return new ViewModel(['access' => $access, 'form' => $form]);
            }

            //ACCESS DENIED
        } else {
            $this->getResponse()->setStatusCode(401);
            $model = new ViewModel();
            $model->setTemplate('error/401');
            return $model;
        }
    }

    /**
     * Update company.
     *
     * @return \Zend\Http\Response|ViewModel
     */
    public function updateAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $companyService = $sm->get('Stjornvisi\Service\Company');

        //COMPANY FOUND
        //
        if (($company = $companyService->get($this->params()->fromRoute('id', 0))) != false) {
            $authService = new AuthenticationService();
            $access = $userService->getTypeByCompany(
                ($authService->hasIdentity()) ? $authService->getIdentity()->id : null,
                $company->id
            );

            //ACCESS GRANTED
            //
            if ($access->is_admin || $access->type != null) {
                $form = $sm->get('Stjornvisi\Form\Company');
                $form->setIdentifier($company->id)
                    ->setAttribute('action', $this->url()->fromRoute('fyrirtaeki/update', ['id'=>$company->id]));
                //POST
                //  http post request
                if ($this->request->isPost()) {
                    $form->setData($this->request->getPost());
                    if ($form->isValid()) {
                        $data = $form->getData();
                        unset($data['submit']);
                        $companyService->update($company->id, $data);
                        return $this->redirect()->toRoute('fyrirtaeki/index', ['id'=>$company->id]);
                    } else {
                        $this->getResponse()->setStatusCode(400);
                        return new ViewModel(
                            [
                            'company' => $company,
                            'access' => $access,
                            'form' => $form
                            ]
                        );
                    }
                //QUERY
                //  http get request
                } else {
                    $form->bind(new ArrayObject($company));
                    return new ViewModel(
                        [
                        'company' => $company,
                        'access' => $access,
                        'form' => $form
                        ]
                    );
                }

            //ACCESS DENIED
            } else {
                $this->getResponse()->setStatusCode(401);
                $model = new ViewModel();
                $model->setTemplate('error/401');
                return $model;
            }
        //COMPANY NOT FOUND
        //  404
        } else {
            return $this->notFoundAction();
        }
    }

    /**
     * Delete company.
     *
     * @return \Zend\Http\Response
     */
    public function deleteAction()
    {
        $sm = $this->getServiceLocator();
        $userService = $sm->get('Stjornvisi\Service\User');
        $companyService = $sm->get('Stjornvisi\Service\Company');

        //COMPANY FOUND
        //
        if (($company = $companyService->get($this->params()->fromRoute('id', 0))) != false) {
            $authService = new AuthenticationService();
            $access = $userService->getTypeByCompany(
                ($authService->hasIdentity()) ? $authService->getIdentity()->id : null,
                $company->id
            );

            //ACCESS GRANTED
            //
            if ($access->is_admin || $access->type == 1) {
                $companyService->delete($company->id);
                return $this->redirect()->toRoute('fyrirtaeki');
                //ACCESS DENIED
                //
            } else {
                $this->getResponse()->setStatusCode(401);
                $model = new ViewModel();
                $model->setTemplate('error/401');
                return $model;
            }

            //COMPANY NOT FOUND
            //  404
        } else {
            return $this->notFoundAction();
        }
    }

    /**
     * Export all companies as a CSV list file.
     *
     * @return CsvModel
     */
    public function exportAction()
    {
        $sm = $this->getServiceLocator();
        $companyService = $sm->get('Stjornvisi\Service\Company');

        $csv = new Csv();
        $csv->setHeader(
            [
            'Nafn',
            'Kennitala',
            'Heimilisfang.',
            'Póstnúmer',
            'Stærð',
            'Tegung',
            'Stofnað',
            ]
        );
        $csv->setName('fyrirtaekjalisti-'.date('Y-m-d-H:i').'.csv');
        $companies = $companyService->fetchAll([]);
        foreach ($companies as $result) {
            $csv->add(
                [
                'name' => $result->name,
                'ssn' => $result->ssn,
                'address' => $result->address,
                'zip' => $result->zip,
                'number_of_employees' => $result->number_of_employees,
                'business_type' => $result->business_type,
                'created' => $result->created->format('Y-m-d'),
                ]
            );
        }

        $model = new CsvModel();
        $model->setData($csv);

        return $model;
    }
}
