<?php

namespace App\Http\Controllers;

use App\FacebookModel;
use App\FeedImagesModel;
use App\FeedsModel;
use App\GroupsModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Facebook\Facebook;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class FacebookController extends Controller
{
    protected $fb;
    function __construct()
    {
        $this->middleware('auth');

        $this->fb = new \Facebook\Facebook([
            'app_id' => 'xxxxxxxxxxx',
            'app_secret' => 'xxxxxxxxxxxxxxxxxx',
            'default_graph_version' => 'v2.8',
            'http_client_handler' => 'stream'
            //'default_access_token' => '{access-token}', // optional
        ]);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function connectFB()
    {

        $facebookModel = FacebookModel::where('userId',Auth::id())->first();
        if(empty($facebookModel)){
            $helper = $this->fb->getRedirectLoginHelper();

            $permissions = ['email','user_managed_groups']; // Optional permissions
            $loginUrl = $helper->getLoginUrl('http://adanjz.com/callback/true', $permissions);

            header('location:'.$loginUrl);
            die();
        }else{
            return $this->callback();
        }


        // Use one of the helper classes to get a Facebook\Authentication\AccessToken entity.
//           $helper = $fb->getRedirectLoginHelper();
//           $helper = $fb->getJavaScriptHelper();
//           $helper = $fb->getCanvasHelper();
//           $helper = $fb->getPageTabHelper();
//        dd($helper);

    }

    public function callback(Request $request=NULL,$v=false){
        $showGroups = $v;
        try {
            // Get the \Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.

            $facebookModel = FacebookModel::where('userId',Auth::id())->first();
            if(empty($facebookModel)){
                $facebookModel = new FacebookModel();
                $helper = $this->fb->getRedirectLoginHelper();
                if (isset($_GET['state'])) {
                    $helper->getPersistentDataHandler()->set('state', $_GET['state']);
                }
                $accessToken = $helper->getAccessToken();
                $facebookModel->accessToken = $accessToken;
                $facebookModel->userId = Auth::id();
                $facebookModel->save();
            }else{
                $accessToken = $facebookModel->accessToken;
            }


//
//            $response = $this->fb->get('/me', $accessToken);
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

            $displayablePublicGroups = [];
            $publicGroups = GroupsModel::where('privacy','OPEN')->where('userId','!=',Auth::id())->distinct('name')->get();
            foreach ($publicGroups as $groups){
                $groupUser = GroupsModel::where('groupId',$groups->groupId)->where('userId',Auth::id())->first();
                if(empty($groupUser)){
                    $displayablePublicGroups[] = $groups;
                }
            }
            return view('home')->with('groups',$this->getMyGroups())->with('publicGroups',$displayablePublicGroups);




//
//        $me = $response->getGraphUser();
//        echo 'Logged in as ' . $me->getName();
//        $groups = $this->fb->get('/me/groups',$accessToken);
//        $gss = $groups->getBody();
//        foreach(json_decode($gss)->data as $group){
//            $feeds = $fb->get('/'.$group->id.'/feed',$accessToken);
//            $feedsObj = json_decode($feeds->getBody());
//            dd($feedsObj->data);
//        }
    }
    public function showMyGroups(){
        $displayablePublicGroups = [];
        $publicGroups = GroupsModel::where('privacy','OPEN')->where('userId','!=',Auth::id())->distinct('name')->get();
        foreach ($publicGroups as $groups){
            $groupUser = GroupsModel::where('groupId',$groups->groupId)->where('userId',Auth::id())->first();
            if(empty($groupUser)){
                $displayablePublicGroups[] = $groups;
            }
        }
        return view('home')->with('groups',$this->getMyGroups())->with('publicGroups',$displayablePublicGroups);
    }
    public function getMyFBId(){
        $response = $this->fb->get('/me',$this->getAccessToken());
        $me = $response->getGraphUser();
        return $me->getId();
    }
    public function getMyGroups(){
        $facebookGroups = GroupsModel::where('userId',Auth::id())->get();
        if(empty($facebookGroups) || count($facebookGroups) == 0){
            $facebookGroups = $this->refreshGroups();
            $facebookGroups = $this->checkGroupMember();
        }

        return $facebookGroups;
    }
    public function checkGroupMember(){
        $groups = GroupsModel::where('userId','!=',Auth::id())->where('owner',1)->get();
        foreach($groups as $group){
            $membersReq = $this->fb->get('/'.$group->groupId.'/members?fields=about&limit=99999999999',$this->getAccessToken($group->userId));
            $members = (array)json_decode($membersReq->getBody());

            if(in_array($this->getMyFBId(),$members)){
                $newGroupConnection = new GroupsModel();
                $newGroupConnection->groupId = $group->id;
                $newGroupConnection->userId = Auth::id();
                $newGroupConnection->privacy = $group->privacy;
                $newGroupConnection->name = $group->name;
                $newGroupConnection->owner = 0;
                $newGroupConnection->save();
            }


        }
    }
    public function getAccessToken($userId = false){
        if(empty($userId)){
            $userId = Auth::id();
        }
        $facebookModel = FacebookModel::where('userId',$userId)->first();
        if(empty($facebookModel)){
            $accessToken = $this->connectFB();
        }else{
            $accessToken = $facebookModel->accessToken;
        }
        return $accessToken;
    }

    public function refreshGroups(){
        $groups = $this->fb->get('/me/groups',$this->getAccessToken());
        $gss = $groups->getBody();
        foreach(json_decode($gss)->data as $group){
            $facebookGroup = GroupsModel::where('groupId',$group->id)->first();
            if(empty($facebookGroup)){
                $facebookGroup = new GroupsModel();
                $facebookGroup->groupId = $group->id;
                $facebookGroup->userId = Auth::id();
                $facebookGroup->privacy = $group->privacy;
                $facebookGroup->name = $group->name;
                $facebookGroup->owner = 1;
                $facebookGroup->save();
            }
        }
        return GroupsModel::where('userId',Auth::id())->get();
    }
    public function refreshGroupFeeds($groupId=''){

        $fbGroup = GroupsModel::where('groupId',$groupId)->where('owner',1)->first();
        $fbGroup->merged = 1;
        $fbGroup->save();



        $groupOwnerId = $fbGroup->userId;


        $feeds = $this->fb->get('/'.$groupId.'/feed?fields=attachments.limit(10){url,media,description_tags,subattachments},message,updated_time,place,from&limit=100',$this->getAccessToken($groupOwnerId));
        $feedsObj = json_decode($feeds->getBody());
        if($groupId != '41111670428'){
            echo $feeds->getBody();
            die();
        }
        $feedsObjData = $feedsObj->data;
        foreach($feedsObjData as $feed){
            $feedNew = FeedsModel::where('feedId',$feed->id)->first();
            if(empty($feedNew)){

                if(!empty($feed->message) && (strpos($feed->message,'FREE') || strpos($feed->message,'$'))) {
                    $updatedTime = str_replace('T',' ',$feed->updated_time);
                    $updatedTime = str_replace('+0000','',$updatedTime);

                    $dt = Carbon::parse($updatedTime);
                    if($dt->diffInDays(Carbon::now()) > 7){
                        break;
                    }

                    $feedNew = new FeedsModel();
                    $feedNew->feedId = $feed->id;
                    $feedNew->message = $feed->message;
                    $feedNew->groupId = $groupId;
                    $feedNew->fbUser = $feed->from->id;
                    $feedNew->updated_time = $updatedTime;
                    if(strpos($feed->message,'FREE')){
                        $feedNew->price = 0;
                    }else{
                        $pattern = '/(?:$|[$])\s*\d+(?:\.\d{2})?/';
                        if (!preg_match($pattern, $feed->message, $matches)) {
                            $price = 0;
                        }else{
                            $price = substr($matches[0],1);
                        }
                        $feedNew->price = str_replace(',','',$price);

                    }
                    $feedNew->save();
                    if(!empty($feed->attachments->data[0]->media)){
                        $feedImage = new FeedImagesModel();
                        $feedImage->feedId = $feed->id;
                        $feedImage->image = $feed->attachments->data[0]->media->image->src;
                        $feedImage->save();
                    }else if(!empty($feed->attachments->data[0]->subattachments->data)){
                        foreach($feed->attachments->data[0]->subattachments->data as $image){
                            $feedImage = new FeedImagesModel();
                            $feedImage->feedId = $feed->id;
                            $feedImage->image = $image->media->image->src;
                            $feedImage->save();
                        }
                    }

                }
            }else{
                break;
            }
        }
        while(!empty($feedsObj->data)){
//            dd($feedsObj->paging->next);
            $feeds = file_get_contents($feedsObj->paging->next);
            $feedsObj = json_decode($feeds);
            $feedsObjData = $feedsObj->data;
            if(!empty($feedsObjData)){
                foreach($feedsObjData as $feed){
                    $feedNew = FeedsModel::where('feedId',$feed->id)->first();
                    if(empty($feedNew)){

                        if(!empty($feed->message) && (strpos($feed->message,'FREE') || strpos($feed->message,'$'))) {
                            $updatedTime = str_replace('T',' ',$feed->updated_time);
                            $updatedTime = str_replace('+0000','',$updatedTime);

                            $dt = Carbon::parse($updatedTime);
                            if($dt->diffInDays(Carbon::now()) > 7){
                                break 2;
                            }
                            $feedNew = new FeedsModel();
                            $feedNew->feedId = $feed->id;
                            $feedNew->message = $feed->message;
                            $feedNew->groupId = $groupId;
                            $feedNew->fbUser = $feed->from->id;
                            $feedNew->updated_time = $updatedTime;
                            if(strpos($feed->message,'FREE')){
                                $feedNew->price = 0;
                            }else{
                                $pattern = '/(?:$|[$])\s*\d+(?:\.\d{2})?/';
                                if (!preg_match($pattern, $feed->message, $matches)) {
                                    $price = 0;
                                }else{
                                    $price = substr($matches[0],1);
                                }
                                $feedNew->price = str_replace(',','',$price);
                            }
                            $feedNew->save();
                            if(!empty($feed->attachments->data[0]->media)){
                                $feedImage = new FeedImagesModel();
                                $feedImage->feedId = $feed->id;
                                $feedImage->image = $feed->attachments->data[0]->media->image->src;
                                $feedImage->save();
                            }else if(!empty($feed->attachments->data[0]->subattachments->data)){
                                foreach($feed->attachments->data[0]->subattachments->data as $image){
                                    $feedImage = new FeedImagesModel();
                                    $feedImage->feedId = $feed->id;
                                    $feedImage->image = $image->media->image->src;
                                    $feedImage->save();
                                }
                            }

                        }
                    }else{
                        break 2;
                    }
                }
            }
        }
        return FeedsModel::where('groupId',$groupId)->get();
    }
    public function getGroupFeeds($groupId){
        $feeds = FeedsModel::where('groupId',$groupId)->get();
        if(empty($feeds) || count($feeds) == 0){
            $feeds = $this->refreshGroupFeeds($groupId);
        }
        return $feeds;
    }
    public function importGroupFeeds($groupId){
        $facebookGroup =  GroupsModel::where('userId',Auth::id())->where('groupId',$groupId)->first();
        if(empty($facebookGroup)){
            $group = GroupsModel::where('groupId',$groupId)->first();
            $facebookGroup = new GroupsModel();
            $facebookGroup->groupId = $groupId;
            $facebookGroup->userId = Auth::id();
            $facebookGroup->privacy = 'OPEN';
            $facebookGroup->name = $group->name;
            $facebookGroup->merged = 1;
            $facebookGroup->owner = 0;
            $facebookGroup->save();
        }


        $this->getGroupFeeds($groupId);
        return $this->showAllMyProducts();
    }
    public function getAllGroupFeeds($priceMin=0,$priceMax=9999999999999,$groupsSelected,$query,$page=50){
        $groups = [];
        $groupsModel = GroupsModel::where('userId',Auth::id())->get();
        foreach ($groupsModel as $group) {
            if(empty($groupsSelected) || in_array($group->groupId,$groupsSelected)){

                $groups[] = $group->groupId;
            }

        }

        $feeds = FeedsModel::whereIn('groupId',$groups)
            ->where('price','>',$priceMin)
            ->where('price','<',$priceMax)
            ->where('message','like','%'.$query.'%')
            ->orderBy('updated_time','desc')
            ->paginate($page);
        return $feeds;
    }
    public function refreshProducts(){
        $groupsToUse = [];
        $groupsModel = GroupsModel::where('userId',Auth::id())->get();
        foreach($groupsModel as $group){
            if($group->owner == 0 || $group->merged){
                $this->refreshGroupFeeds($group->groupId);
            }
        }
        return $this->showAllMyProducts();
    }
    public function showAllMyProducts(){

        $images = [];
        $priceMin = 0;
        $priceMax = -1;
        $groupsSelected = [];
        $query = '';
        if(!empty($_GET['amountMin'])) {
            $priceMin = $_GET['amountMin'];

            $priceMax = $_GET['amountMax'];
        }

        if(!empty($_GET['groups'])){
            $groupsSelected = $_GET['groups'];
        }

        if(!empty($_GET['q'])){
            $query = $_GET['q'];
        }

        if(!empty($_GET['page'])){
            $page = $_GET['page'];
        }else{
            $page = 50;
        }

        $groups = [];
        $groupsList = [];
        $groupsModel = GroupsModel::where('userId',Auth::id())->where('merged',1)->get();
        foreach ($groupsModel as $group) {
            $groups[] = $group->groupId;
            $groupsList[$group->groupId] = $group->name;
        }

        $maxPrice = FeedsModel::whereIn('groupId',$groups)->orderBy('updated_time','desc')->max('price');
        if($priceMax == -1){
            $priceMax = $maxPrice;
        }

        $feeds = $this->getAllGroupFeeds($priceMin,$priceMax,$groupsSelected,$query,$page);
        foreach($feeds as $feed){
            $imagesFeeds = FeedImagesModel::where('feedId',$feed->feedId)->get();
            foreach($imagesFeeds as $if){
                $images[$feed->id][] = $if->image;
            }
        }

        return view('products')->with('products',$feeds)->with('images',$images)->with('min',0)->with('max',$maxPrice)->with('groups',$groupsList)
            ->with('priceMin',$priceMin)->with('priceMax',$priceMax)->with('groupsSelected',$groupsSelected)->with('q',$query);
    }
}
