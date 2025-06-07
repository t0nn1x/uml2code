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
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ActionHistoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ActionHistory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Action History')
            ->setEntityLabelInPlural('Action History')
            ->setSearchFields(['actionType', 'diagramType', 'programmingLanguage', 'diagramName'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user', 'User')
                ->setRequired(false)
                ->hideOnForm(),
            ChoiceField::new('actionType', 'Action')
                ->setChoices([
                    'Convert' => ActionHistory::ACTION_CONVERT,
                    'Parse' => ActionHistory::ACTION_PARSE,
                    'Generate' => ActionHistory::ACTION_GENERATE,
                ])
                ->hideOnForm(),
            TextField::new('diagramType', 'Diagram Type')->hideOnForm(),
            TextField::new('programmingLanguage', 'Language')->hideOnForm(),
            TextField::new('diagramName', 'Diagram Name')->hideOnForm(),
            IntegerField::new('diagramSize', 'Size (bytes)')->hideOnForm(),
            IntegerField::new('totalLinesOfCode', 'Lines of Code')->hideOnForm(),
            TextField::new('generatorVersion', 'Generator Version')->hideOnForm(),
            DateTimeField::new('createdAt', 'Created At')->hideOnForm(),
        ];

        // Show files content only on detail page
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = CodeEditorField::new('files', 'Files')
                ->setLanguage('javascript')
                ->hideOnForm()
                ->setNumOfRows(20)
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                });
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
                    'Convert' => ActionHistory::ACTION_CONVERT,
                    'Parse' => ActionHistory::ACTION_PARSE,
                    'Generate' => ActionHistory::ACTION_GENERATE,
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
