@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">My Groups</div>
                <div class="panel-body">
                    <?php if(empty($groups)){
                        echo '<a href="/facebookLogin" class="btn btn-primary">Connect with facebook groups</a>';
                    }else{
                        foreach($groups as $group){
                            switch($group->privacy){
                                case 'CLOSED':
                                    $labelType = 'danger';
                                    break;
                                case 'OPEN':
                                    $labelType = 'success';
                                    break;
                                case 'SECRET':
                                    $labelType = 'primary';
                                    break;
                                default:
                                    $labelType = 'default';
                            }
                            echo '<div class="panel panel-'.(($group->merged)?'success':'danger').'">
                                      <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                '.$group->name.'
                                                <span class="label label-'.$labelType.'">'.$group->privacy.'</span>
                                            </div>
                                            <div class="col-md-4">
                                                '.((!$group->merged)?'
                                                <a href="/importGroup/'.$group->groupId.'" class="btn btn-primary">Import products</a>':'Merged').'
                                            </div>
                                        </div>
                                      </div>
                            </div>';
                        }
                    }
                    ?>

                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">Public groups</div>
                <div class="panel-body">
                    <?php
                        if(!empty($publicGroups)){
                        foreach($publicGroups as $group){
                            echo '<div class="panel panel-'.(($group->merged)?'success':'danger').'">
                                      <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                '.$group->name.'
                                            </div>
                                            <div class="col-md-3">
                                                <a href="/importGroup/'.$group->groupId.'" class="btn btn-primary">Add to my list</a>
                                            </div>
                                        </div>
                                      </div>
                            </div>';
                    }
                    }
                    ?>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
