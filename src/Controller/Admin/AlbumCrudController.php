<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AlbumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('artist');
        yield TextField::new('name');
        yield NumberField::new('year_created');
        yield TextField::new('cover');
        yield BooleanField::new('ep');
        yield BooleanField::new('single');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('artist');
    }
}
