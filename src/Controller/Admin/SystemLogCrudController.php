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
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class SystemLogCrudController extends AbstractCrudController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return SystemLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('system_logs.list', [], 'admin'))
            ->setEntityLabelInPlural($this->translator->trans('system_logs.list', [], 'admin'))
            ->setPageTitle(Crud::PAGE_INDEX, $this->translator->trans('system_logs.list', [], 'admin'))
            ->setPageTitle(Crud::PAGE_DETAIL, $this->translator->trans('system_logs.details', [], 'admin'))
            ->setSearchFields(['message', 'channel', 'ipAddress', 'requestUri'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->setLabel($this->translator->trans('table.headers.id', [], 'admin'))->hideOnForm(),
            ChoiceField::new('level')->setLabel($this->translator->trans('table.headers.level', [], 'admin'))
                ->setChoices([
                    $this->translator->trans('system_logs.levels.emergency', [], 'admin') => SystemLog::LEVEL_EMERGENCY,
                    $this->translator->trans('system_logs.levels.alert', [], 'admin') => SystemLog::LEVEL_ALERT,
                    $this->translator->trans('system_logs.levels.critical', [], 'admin') => SystemLog::LEVEL_CRITICAL,
                    $this->translator->trans('system_logs.levels.error', [], 'admin') => SystemLog::LEVEL_ERROR,
                    $this->translator->trans('system_logs.levels.warning', [], 'admin') => SystemLog::LEVEL_WARNING,
                    $this->translator->trans('system_logs.levels.notice', [], 'admin') => SystemLog::LEVEL_NOTICE,
                    $this->translator->trans('system_logs.levels.info', [], 'admin') => SystemLog::LEVEL_INFO,
                    $this->translator->trans('system_logs.levels.debug', [], 'admin') => SystemLog::LEVEL_DEBUG,
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
            TextField::new('channel')->setLabel($this->translator->trans('table.headers.channel', [], 'admin'))->hideOnForm(),
            TextareaField::new('message')->setLabel($this->translator->trans('table.headers.message', [], 'admin'))
                ->setMaxLength(200)
                ->hideOnForm(),
            AssociationField::new('user')->setLabel($this->translator->trans('table.headers.user', [], 'admin'))
                ->hideOnForm(),
            TextField::new('ipAddress')->setLabel($this->translator->trans('table.headers.ip_address', [], 'admin'))->hideOnForm(),
            TextField::new('requestUri')->setLabel($this->translator->trans('table.headers.request_uri', [], 'admin'))->hideOnForm(),
            DateTimeField::new('createdAt')->setLabel($this->translator->trans('table.headers.created_at', [], 'admin'))->hideOnForm(),
        ];

        // Show detailed context and extra data only on detail page
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = TextareaField::new('message')->setLabel($this->translator->trans('system_logs.fields.message', [], 'admin'))
                ->setNumOfRows(5)
                ->hideOnForm();
            
            $fields[] = CodeEditorField::new('context')->setLabel($this->translator->trans('system_logs.fields.context', [], 'admin'))
                ->setLanguage('javascript')
                ->hideOnForm()
                ->setNumOfRows(10)
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                });
            
            $fields[] = CodeEditorField::new('extra')->setLabel($this->translator->trans('system_logs.fields.extra', [], 'admin'))
                ->setLanguage('javascript')
                ->hideOnForm()
                ->setNumOfRows(10)
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                });
            
            $fields[] = TextField::new('userAgent')->setLabel($this->translator->trans('system_logs.fields.user_agent', [], 'admin'))->hideOnForm();
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
            ->add(ChoiceFilter::new('level', 'Log Level')
                ->setChoices([
                    '🚨 Emergency' => SystemLog::LEVEL_EMERGENCY,
                    '🔥 Alert' => SystemLog::LEVEL_ALERT,
                    '💥 Critical' => SystemLog::LEVEL_CRITICAL,
                    '❌ Error' => SystemLog::LEVEL_ERROR,
                    '⚠️ Warning' => SystemLog::LEVEL_WARNING,
                    'ℹ️ Notice' => SystemLog::LEVEL_NOTICE,
                    '✅ Info' => SystemLog::LEVEL_INFO,
                    '🔍 Debug' => SystemLog::LEVEL_DEBUG,
                ])
                ->canSelectMultiple()
            )
            ->add(TextFilter::new('channel', 'Channel'))
            ->add(EntityFilter::new('user', 'User'))
            ->add(TextFilter::new('ipAddress', 'IP Address'))
            ->add(DateTimeFilter::new('createdAt', 'Created At'));
    }
} 
