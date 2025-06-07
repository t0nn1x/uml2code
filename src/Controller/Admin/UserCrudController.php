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

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setSearchFields(['email', 'firstName', 'lastName'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email'),
            TextField::new('firstName'),
            TextField::new('lastName'),
            ArrayField::new('roles')
                ->setHelp('Available roles: ROLE_USER, ROLE_ADMIN, ROLE_PREMIUM'),
            ChoiceField::new('subscriptionStatus')
                ->setChoices([
                    'Free' => 'free',
                    'Premium' => 'premium',
                    'Enterprise' => 'enterprise'
                ]),
            BooleanField::new('isVerified'),
            DateTimeField::new('lastLoginAt')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        $deleteAction = Action::new('safeDelete', 'Delete', 'fa fa-trash')
            ->linkToCrudAction('delete')
            ->addCssClass('btn btn-danger')
            ->setHtmlAttributes(['onclick' => 'return confirm("⚠️ WARNING: This will permanently delete the user and all related data (OAuth connections, reset password requests)!\\n\\nThis action cannot be undone. Are you sure?")']);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $deleteAction)
            ->setPermission('safeDelete', 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isVerified'))
            ->add(ChoiceFilter::new('subscriptionStatus')
                ->setChoices([
                    'Free' => 'free',
                    'Premium' => 'premium',
                    'Enterprise' => 'enterprise'
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
