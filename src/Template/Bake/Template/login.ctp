<?php
/**
 * @var \<%= $namespace %>\View\AppView $this
 */
?>
<div class="<%= $pluralVar %> form col-xs-12">
<?= $this->Flash->render('auth') ?>
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __('Please enter your username and password') ?></legend>
        <?= $this->Form->input('username') ?>
        <?= $this->Form->input('password') ?>
    </fieldset>
    <?= $this->Form->button(__('Login')); ?>
    <?= $this->Form->end() ?>
</div>
