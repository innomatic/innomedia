<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  2008-2014 Innoteam Srl
 * @license    http://www.innomatic.io/license/   BSD License
 * @link       http://www.innomatic.io
 * @since      Class available since Release 1.0.0
 */
namespace Innomedia;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright Copyright 2008-2013 Innoteam Srl
 * @since 1.0
 */
class EmptyBlock extends Block
{

    public function run(\Innomatic\Webapp\WebAppRequest $request, \Innomatic\Webapp\WebAppResponse $response)
    {}
}

?>
