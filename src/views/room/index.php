<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider ArrayDataProvider */
/* @var $filters array */
/* @var $sessionId string */

$this->title = 'WhatsApp Rooms';
?>

<div class="whatsapp-rooms-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- Filter Form -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Filter Rooms</h3>
        </div>
        <div class="panel-body">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'options' => ['class' => 'form-inline']
            ]); ?>

            <div class="form-group">
                <?= Html::label('Room Type:', 'filter-type', ['class' => 'control-label']) ?>
                <?= Html::dropDownList('type', $filters['type'] ?? '', [
                    '' => 'All Types',
                    'individual' => 'Individual Chats',
                    'group' => 'Group Chats',
                ], ['class' => 'form-control', 'id' => 'filter-type']) ?>
            </div>

            <div class="form-group">
                <?= Html::label('Group Only:', 'filter-group', ['class' => 'control-label']) ?>
                <?= Html::dropDownList('isGroup', isset($filters['isGroup']) ? (int)$filters['isGroup'] : '', [
                    '' => 'All',
                    '1' => 'Groups Only',
                    '0' => 'Individual Only',
                ], ['class' => 'form-control', 'id' => 'filter-group']) ?>
            </div>

            <div class="form-group">
                <?= Html::label('New Messages:', 'filter-unread', ['class' => 'control-label']) ?>
                <?= Html::dropDownList('hasNewMessages', isset($filters['hasNewMessages']) ? (int)$filters['hasNewMessages'] : '', [
                    '' => 'All',
                    '1' => 'With New Messages',
                    '0' => 'No New Messages',
                ], ['class' => 'form-control', 'id' => 'filter-unread']) ?>
            </div>

            <div class="form-group">
                <?= Html::label('Name:', 'filter-name', ['class' => 'control-label']) ?>
                <?= Html::textInput('name', $filters['name'] ?? '', [
                    'class' => 'form-control',
                    'placeholder' => 'Search by name...',
                    'id' => 'filter-name'
                ]) ?>
            </div>

            <div class="form-group">
                <?= Html::submitButton('Filter', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Clear', ['index'], ['class' => 'btn btn-default']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Quick Filter Buttons -->
    <div class="btn-group" style="margin-bottom: 15px;">
        <?= Html::a('All Rooms', ['list'], ['class' => 'btn btn-default']) ?>
        <?= Html::a('Groups', ['groups'], ['class' => 'btn btn-info']) ?>
        <?= Html::a('Individual', ['individual'], ['class' => 'btn btn-info']) ?>
        <?= Html::a('Unread', ['unread'], ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Archived', ['archived'], ['class' => 'btn btn-secondary']) ?>
        <?= Html::a('Pinned', ['pinned'], ['class' => 'btn btn-success']) ?>
    </div>

    <!-- Results Table -->
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-bordered'],
        'columns' => [
            [
                'attribute' => 'name',
                'label' => 'Chat Name',
                'format' => 'text',
            ],
            [
                'attribute' => 'type',
                'label' => 'Type',
                'value' => function ($model) {
                    return ucfirst($model['type']);
                },
                'filter' => ['individual' => 'Individual', 'group' => 'Group'],
            ],
            [
                'attribute' => 'unreadCount',
                'label' => 'Unread',
                'value' => function ($model) {
                    return $model['unreadCount'] > 0 ? $model['unreadCount'] : '-';
                },
                'contentOptions' => function ($model) {
                    return $model['unreadCount'] > 0 ? ['class' => 'text-danger font-weight-bold'] : [];
                },
            ],
            [
                'attribute' => 'lastMessage',
                'label' => 'Last Message',
                'format' => 'raw',
                'value' => function ($model) {
                    if (!$model['lastMessage']) {
                        return '<em class="text-muted">No messages</em>';
                    }
                    
                    $body = $model['lastMessage']['body'] ?? '';
                    if (strlen($body) > 50) {
                        $body = substr($body, 0, 50) . '...';
                    }
                    
                    return Html::encode($body);
                },
            ],
            [
                'attribute' => 'timestamp',
                'label' => 'Last Activity',
                'format' => 'datetime',
                'value' => function ($model) {
                    return $model['timestamp'];
                },
            ],
            [
                'label' => 'Status',
                'format' => 'raw',
                'value' => function ($model) {
                    $badges = [];
                    
                    if ($model['isPinned']) {
                        $badges[] = '<span class="badge badge-success">Pinned</span>';
                    }
                    
                    if ($model['isArchived']) {
                        $badges[] = '<span class="badge badge-secondary">Archived</span>';
                    }
                    
                    if ($model['isMuted']) {
                        $badges[] = '<span class="badge badge-warning">Muted</span>';
                    }
                    
                    if ($model['hasNewMessages']) {
                        $badges[] = '<span class="badge badge-danger">New Messages</span>';
                    }
                    
                    return implode(' ', $badges);
                },
            ],
        ],
        'summary' => 'Showing {begin}-{end} of {totalCount} rooms. Session: ' . Html::encode($sessionId),
        'emptyText' => 'No rooms found with the current filters.',
    ]); ?>

    <!-- Statistics -->
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading">Room Statistics</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Total Rooms:</strong> <?= $dataProvider->getTotalCount() ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Current Filters:</strong> <?= count($filters) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Page:</strong> <?= $dataProvider->getPagination()->getPage() + 1 ?> of <?= $dataProvider->getPagination()->getPageCount() ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Session:</strong> <?= Html::encode($sessionId) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge {
    margin-right: 5px;
}

.form-inline .form-group {
    margin-right: 15px;
    margin-bottom: 10px;
}

.btn-group .btn {
    margin-right: 5px;
}

.panel {
    margin-bottom: 20px;
}
</style>