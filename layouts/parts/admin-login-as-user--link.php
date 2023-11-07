<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

?>
<mc-link v-if="global.auth.is('saasSuperAdmin')" route="auth/asUserId" :params="{id: entity.id}" icon="arrow-up" class="button button--primary button--sm button--icon" right-icon><?= i::__('logar')?></mc-link>