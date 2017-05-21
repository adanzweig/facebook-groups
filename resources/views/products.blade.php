@extends('layouts.app')

@section('content')
    <style>
        .long-text{
            display: none;
        }
        .jscroll-inner{
            -moz-column-count: 4;
            -moz-column-gap: 10px;
            -webkit-column-count: 4;
            -webkit-column-gap: 10px;
            column-count: 4;
            column-gap: 10px;
            width: 100%;
        }
    </style>
    <script type="text/javascript">
        $(function() {
            $( "#slider-range" ).slider({
                range: true,
                min:<?=$min?>,
                max:<?= $max?>,
                values: [ <?php echo $priceMin; ?>, <?php echo $priceMax; ?> ],
                slide: function( event, ui ) {
                    $( "#amountMin" ).val( ui.values[ 0 ]);
                    $( "#amountMax" ).val( ui.values[ 1 ] );
                }
            });
        });
        function showMore(id){
            $('#short-text-'+id).hide();
            $('#long-text-'+id).show();
        }
        function showLess(id){
            $('#short-text-'+id).show();
            $('#long-text-'+id).hide();
        }
    </script>
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="GET" action="/products">
                            <div class="row">
                                <div class="col-md-12" style="margin-bottom: 10px;">
                                    <label>Title filter</label>
                                        <input type="text" name="q" value="<?=$q?>" class="form-control"/>
                                </div>
                                <div class="col-md-12" style="margin-bottom: 10px;">
                                    <label>Price filter</label>
                                    <div class="row">
                                        <div class="col-md-5" style="padding:0px;">
                                            <div class="input-group">
                                                <span class="input-group-addon" style="background-color:#fff; border:0; color:#f6931f; font-weight:bold;">$</span>
                                                <input type="text" id="amountMin" class="form-control" value="<?=$priceMin?>" name="amountMin" style="padding:0px;border:0; color:#f6931f; font-weight:bold;">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            -
                                        </div>
                                        <div class="col-md-5" style="padding:0px;">
                                            <div class="input-group">
                                                <span class="input-group-addon" style="background-color:#fff; border:0; color:#f6931f; font-weight:bold;">$</span>
                                                <input type="text" id="amountMax" class="form-control" value="<?=$priceMax?>" name="amountMax" style="padding:0px;border:0; color:#f6931f; font-weight:bold;">
                                            </div>
                                        </div>
                                    </div>


                                    <div id="slider-range" style="width:100%;"></div>
                                </div>
                                <div class="col-md-12">
                                    <label>Group filter</label>
                                    <ul style="padding:0;">
                                    <?php
                                        foreach($groups as $k=>$group){
                                            $checked = false;

                                            if(in_array($k,$groupsSelected)){
                                                $checked = true;

                                            }
                                            echo '<li style="list-style:none; margin: 5px 0; height: auto;" class="form-control"><input type="checkbox" name="groups[]" value="'.$k.'" '.(($checked)?'checked="checked"':'').'> '.$group.'</li>';
                                        }
                                    ?>
                                    </ul>
                                </div>
                                <div class="col-md-12">
                                    <input type="submit">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="panel panel-default">
                    <div class="panel-heading">Products</div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="/refreshProducts" class="btn btn-primary">Refresh products</a>
                            </div>
                            <div class="col-md-6">
                                <a href="/importMoreGroups" class="btn btn-primary">Import more groups</a>
                            </div>
                        </div>
                        <div class="row" style="margin-top:20px;padding: 20px;">
                            <div id="products" class="row list-group">
                                <div class="infinite-scroll" style="">
                                    <?php if(empty($products)){
                                        echo '<a href="/facebookLogin" class="btn btn-primary">Connect with facebook</a>';
                                    }else{
                                        foreach($products as $product){
                                            echo '<div class="item" style="display: inline-block;">
                                                    <div class="thumbnail">
                                                        <img class="group list-group-image" src="'.(!empty($images[$product->id])?$images[$product->id][0]:'').'" alt="" />
                                                        <div class="caption">
                                                            <p class="group inner list-group-item-text">
                                                                <span id="short-text-'.$product->id.'">'.nl2br(substr($product->message,0,100)).'<a href="javascript:showMore('.$product->id.')">...</a></span>
                                                                <span class="long-text" id="long-text-'.$product->id.'">'.nl2br($product->message).'<a href="javascript:showLess('.$product->id.')"> <small>< Show less</small></a></span>
                                                            </p>
                                                            <p><b><small>'. \Carbon\Carbon::createFromTimeStamp(strtotime($product->updated_time))->diffForHumans().'</small></b></p>
                                                            <div class="row">
                                                                <div class="col-xs-12">
                                                                    <p class="lead">
                                                                        $'.$product->price.'</p>
                                                                </div>
                                                                <div class="col-xs-12">';
                                                                    $ids = explode('_',$product->feedId);
                                                                    echo '<a class="btn btn-success" target="_blank" href="https://www.facebook.com/groups/'.$ids[0].'/permalink/'.$ids[1].'?sale_post_id='.$ids[1].'">Add to cart</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>';
                                        }
                                    }
                                ?>
                                    <div style="display: none">
                                        {{ $products->appends(\Illuminate\Support\Facades\Input::except('page'))->links() }}

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
