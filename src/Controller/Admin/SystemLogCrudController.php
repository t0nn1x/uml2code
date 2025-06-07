<?php

namespace App\Controller\Admin;

use App\Entity\SystemLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SystemLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SystemLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('System Log')
            ->setEntityLabelInPlural('System Logs')
            ->setSearchFields(['message', 'channel', 'ipAddress', 'requestUri'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            ChoiceField::new('level', 'Level')
                ->setChoices([
                    'Emergency' => SystemLog::LEVEL_EMERGENCY,
                    'Alert' => SystemLog::LEVEL_ALERT,
                    'Critical' => SystemLog::LEVEL_CRITICAL,
                    'Error' => SystemLog::LEVEL_ERROR,
                    'Warning' => SystemLog::LEVEL_WARNING,
                    'Notice' => SystemLog::LEVEL_NOTICE,
                    'Info' => SystemLog::LEVEL_INFO,
                    'Debug' => SystemLog::LEVEL_DEBUG,
                ])
                ->renderAsBadges([
                    SystemLog::LEVEL_EMERGENCY => 'danger',
                    SystemLog::LEVEL_ALERT => 'danger',
                    SystemLog::LEVEL_CRITICAL => 'danger',
                    SystemLog::LEVEL_ERROR => 'danger',
                    SystemLog::LEVEL_WARNING => 'warning',
                    SystemLog::LEVEL_NOTICE => 'info',
                    SystemLog::LEVEL_INFO => 'success',
                    SystemLog::LEVEL_DEBUG => 'secondary',
                ])
                ->hideOnForm(),
            TextField::new('channel', 'Channel')->hideOnForm(),
            TextareaField::new('message', 'Message')
                ->setMaxLength(200)
                ->hideOnForm(),
            AssociationField::new('user', 'User')
                ->hideOnForm(),
            TextField::new('ipAddress', 'IP Address')->hideOnForm(),
            TextField::new('requestUri', 'Request URI')->hideOnForm(),
            DateTimeField::new('createdAt', 'Created At')->hideOnForm(),
        ];

        // Show detailed context and extra data only on detail page
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = TextareaField::new('message', 'Full Message')
                ->setNumOfRows(5)
                ->hideOnForm();
            
            $fields[] = CodeEditorField::new('context', 'Context')
                ->setLanguage('javascript')
                ->hideOnForm()
                ->setNumOfRows(10)
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                });
            
            $fields[] = CodeEditorField::new('extra', 'Extra Data')
                ->setLanguage('javascript')
                ->hideOnForm()
                ->setNumOfRows(10)
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                });
            
            $fields[] = TextField::new('userAgent', 'User Agent')->hideOnForm();
        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('level')
                ->setChoices([
                    'Emergency' => SystemLog::LEVEL_EMERGENCY,
                    'Alert' => SystemLog::LEVEL_ALERT,
                    'Critical' => SystemLog::LEVEL_CRITICAL,
                    'Error' => SystemLog::LEVEL_ERROR,
                    'Warning' => SystemLog::LEVEL_WARNING,
                    'Notice' => SystemLog::LEVEL_NOTICE,
                    'Info' => SystemLog::LEVEL_INFO,
                    'Debug' => SystemLog::LEVEL_DEBUG,
                ]))
            ->add(TextFilter::new('channel'))
            ->add(EntityFilter::new('user'))
            ->add(TextFilter::new('ipAddress'))
            ->add(DateTimeFilter::new('createdAt'));
    }
} 
