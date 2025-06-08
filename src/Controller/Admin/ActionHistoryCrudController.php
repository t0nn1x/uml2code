<?php

namespace App\Controller\Admin;

use App\Entity\ActionHistory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class ActionHistoryCrudController extends AbstractCrudController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ActionHistory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->translator->trans('action_history.list', [], 'admin'))
            ->setEntityLabelInPlural($this->translator->trans('action_history.list', [], 'admin'))
            ->setPageTitle(Crud::PAGE_INDEX, $this->translator->trans('action_history.list', [], 'admin'))
            ->setPageTitle(Crud::PAGE_DETAIL, $this->translator->trans('action_history.details', [], 'admin'))
            ->setSearchFields(['actionType', 'diagramType', 'programmingLanguage', 'diagramName'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->setLabel($this->translator->trans('table.headers.id', [], 'admin'))->hideOnForm(),
            AssociationField::new('user')->setLabel($this->translator->trans('table.headers.user', [], 'admin'))
                ->setRequired(false)
                ->hideOnForm(),
            ChoiceField::new('actionType')->setLabel($this->translator->trans('table.headers.action', [], 'admin'))
                ->setChoices([
                    $this->translator->trans('action_history.actions.convert', [], 'admin') => ActionHistory::ACTION_CONVERT,
                    $this->translator->trans('action_history.actions.parse', [], 'admin') => ActionHistory::ACTION_PARSE,
                    $this->translator->trans('action_history.actions.generate', [], 'admin') => ActionHistory::ACTION_GENERATE,
                ])
                ->hideOnForm(),
            TextField::new('diagramType')->setLabel($this->translator->trans('table.headers.diagram_type', [], 'admin'))->hideOnForm(),
            TextField::new('programmingLanguage')->setLabel($this->translator->trans('table.headers.language', [], 'admin'))->hideOnForm(),
            TextField::new('diagramName')->setLabel($this->translator->trans('table.headers.diagram_name', [], 'admin'))->hideOnForm(),
            IntegerField::new('diagramSize')->setLabel($this->translator->trans('table.headers.size_bytes', [], 'admin'))->hideOnForm(),
            IntegerField::new('totalLinesOfCode')->setLabel($this->translator->trans('table.headers.lines_of_code', [], 'admin'))->hideOnForm(),
            TextField::new('generatorVersion')->setLabel($this->translator->trans('table.headers.generator_version', [], 'admin'))->hideOnForm(),
            DateTimeField::new('createdAt')->setLabel($this->translator->trans('table.headers.created_at', [], 'admin'))->hideOnForm(),
        ];

        // Show files content only on detail page
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = ArrayField::new('files')->setLabel($this->translator->trans('action_history.fields.files', [], 'admin'))
                ->setTemplatePath('admin/fields/files_viewer.html.twig')
                ->hideOnForm()
                ->hideOnIndex();
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
            ->add(EntityFilter::new('user'))
            ->add(ChoiceFilter::new('actionType')
                ->setChoices([
                    'action_history.actions.convert' => ActionHistory::ACTION_CONVERT,
                    'action_history.actions.parse' => ActionHistory::ACTION_PARSE,
                    'action_history.actions.generate' => ActionHistory::ACTION_GENERATE,
                ]))
            ->add(ChoiceFilter::new('programmingLanguage')
                ->setChoices([
                    'PHP' => 'php',
                    'Java' => 'java',
                    'Python' => 'python',
                    'C#' => 'csharp',
                ]))
            ->add(DateTimeFilter::new('createdAt'));
    }
} 
