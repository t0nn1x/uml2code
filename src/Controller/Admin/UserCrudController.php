<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private LoggerInterface $logger,
        private TranslatorInterface $translator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('user.singular', [], 'admin'))
            ->setEntityLabelInPlural($this->translator->trans('user.list', [], 'admin'))
            ->setPageTitle(Crud::PAGE_INDEX, $this->translator->trans('user.list', [], 'admin'))
            ->setPageTitle(Crud::PAGE_NEW, $this->translator->trans('user.create', [], 'admin'))
            ->setPageTitle(Crud::PAGE_EDIT, $this->translator->trans('user.edit', [], 'admin'))
            ->setPageTitle(Crud::PAGE_DETAIL, $this->translator->trans('user.details', [], 'admin'))
            ->setSearchFields(['email', 'firstName', 'lastName'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->setLabel($this->translator->trans('table.headers.id', [], 'admin'))->hideOnForm(),
            EmailField::new('email')->setLabel($this->translator->trans('table.headers.email', [], 'admin')),
            TextField::new('firstName')->setLabel($this->translator->trans('table.headers.first_name', [], 'admin')),
            TextField::new('lastName')->setLabel($this->translator->trans('table.headers.last_name', [], 'admin')),
            ArrayField::new('roles')->setLabel($this->translator->trans('table.headers.roles', [], 'admin'))
                ->setHelp('Available roles: ROLE_USER, ROLE_ADMIN, ROLE_PREMIUM'),
            ChoiceField::new('subscriptionStatus')->setLabel($this->translator->trans('table.headers.subscription_status', [], 'admin'))
                ->setChoices([
                    $this->translator->trans('user.subscription.free', [], 'admin') => 'free',
                    $this->translator->trans('user.subscription.premium', [], 'admin') => 'premium',
                    $this->translator->trans('user.subscription.enterprise', [], 'admin') => 'enterprise'
                ]),
            BooleanField::new('isVerified')->setLabel($this->translator->trans('table.headers.is_verified', [], 'admin')),
            DateTimeField::new('lastLoginAt')->setLabel($this->translator->trans('table.headers.last_login_at', [], 'admin'))->hideOnForm(),
            DateTimeField::new('createdAt')->setLabel($this->translator->trans('table.headers.created_at', [], 'admin'))->hideOnForm(),
            DateTimeField::new('updatedAt')->setLabel($this->translator->trans('table.headers.updated_at', [], 'admin'))->hideOnForm(),
        ];

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action
                    ->setIcon('fa fa-trash')
                    ->setLabel('Delete')
                    ->addCssClass('text-danger')
                    ->setHtmlAttributes([
                        'onclick' => 'return confirm("⚠️ WARNING: This will permanently delete the user and all related data (OAuth connections, reset password requests)!\\n\\nThis action cannot be undone. Are you sure?")',
                        'title' => 'Delete User',
                        'style' => 'color: #dc3545 !important;'
                    ]);
            })
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isVerified'))
            ->add(ChoiceFilter::new('subscriptionStatus')
                ->setChoices([
                    'user.subscription.free' => 'free',
                    'user.subscription.premium' => 'premium',
                    'user.subscription.enterprise' => 'enterprise'
                ]))
            ->add(DateTimeFilter::new('createdAt'))
            ->add(DateTimeFilter::new('lastLoginAt'));
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        $this->logger->info('User created via admin panel', [
            'admin_user' => $this->getUser()?->getUserIdentifier(),
            'created_user' => $entityInstance->getEmail()
        ]);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        $this->logger->info('User updated via admin panel', [
            'admin_user' => $this->getUser()?->getUserIdentifier(),
            'updated_user' => $entityInstance->getEmail()
        ]);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity($entityManager, $entityInstance): void
    {
        $userEmail = $entityInstance->getEmail();
        $adminUser = $this->getUser()?->getUserIdentifier();
        
        // Prevent self-deletion
        if ($userEmail === $adminUser) {
            $this->logger->warning('Attempted self-deletion prevented', [
                'admin_user' => $adminUser,
                'attempted_target' => $userEmail
            ]);
            throw new \RuntimeException('You cannot delete your own account from the admin panel.');
        }
        
        $this->logger->warning('User deleted via admin panel', [
            'admin_user' => $adminUser,
            'deleted_user' => $userEmail,
            'deleted_user_id' => $entityInstance->getId()
        ]);

        try {
            parent::deleteEntity($entityManager, $entityInstance);
            
            $this->logger->info('User deletion completed successfully', [
                'admin_user' => $adminUser,
                'deleted_user' => $userEmail
            ]);
        } catch (\Exception $e) {
            $this->logger->error('User deletion failed', [
                'admin_user' => $adminUser,
                'deleted_user' => $userEmail,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 
