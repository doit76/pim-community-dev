<?php

namespace Pim\Component\Classification\Repository;

use Akeneo\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Doctrine\Common\Persistence\ObjectRepository;
// TODO: depends on UIBundle sounds weird!!
use Pim\Bundle\UIBundle\Entity\Repository\SearchableRepositoryInterface;

/**
 * Tag repository interface
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface TagRepositoryInterface extends
    ObjectRepository,
    IdentifiableObjectRepositoryInterface,
    SearchableRepositoryInterface
{
}
