<?php

class Am_Plugin_Treeview extends Am_Plugin
{
    public function onUserMenuItems(Am_Event $e)
    {
        $e->addReturn(array($this, 'buildMenu'), 'treeaview');
    }

    public function buildMenu(Am_Navigation_Container $nav, User $user, $order, $config)
    {
        return $nav->addPage(array(
            'id' => 'treeview',
            'controller' => 'treeview',
            'action' => 'index',
            'label' => ___('Treeview'),
            'order' => $order
        ));
    }
}


class TreeviewController extends Am_Mvc_Controller
{
    /** @var User */
    protected $user;
    protected $parent;

    public function preDispatch()
    {
        $this->getDi()->auth->requireLogin($this->getDi()->url('aff/member', null, false));
        $this->user = $this->getDi()->user;
        if (!$this->user->is_affiliate) {
            //throw new Am_Exception_InputError("Sorry, this page is opened for affiliates only");
            $this->_redirect('member');
        }
    }

    public function indexAction()
    {
        $this->view->title = ___("Your downline");
        $user = $this->user;
        function render($upline, $user)
        {
            $downlines = Am_Di::getInstance()->db->select("SELECT * FROM ?_user WHERE aff_id = {$upline['user_id']} AND aff_id != {$user->user_id} ORDER BY LOGIN ASC");
            $html = "";
            if (count($downlines) > 0) {
                $html .= sprintf('<li>
                  <input type="checkbox" class="expander">
                  <span class="expander"></span>
                  <label>%s - %s</label>', $upline['login'], $upline['name_f'] . ' ' . $upline['name_l']);
                $html .= "<ul>";
                foreach ($downlines as $key => $downline) {
                    $html .= render($downline, $user);
                }
                $html .= "</ul>";
                $html .= "</li>";
            } else {
                $html = sprintf('<li>
                  <input type="checkbox" class="expander" disabled>
                  <span class="expander"></span>
                  <label>%s - %s</label>
              </li>', $upline['login'], $upline['name_f'] . ' ' . $upline['name_l']);
            }

            return $html;

        }

        $uplines = $this->getDi()->db->select("SELECT * FROM ?_user WHERE aff_id = {$user->user_id}");
        $html = "";
        $this->parent = $user->user_id;
        foreach ($uplines as $key => $upline) {
            $html .= render($upline, $user);
        }
        
        $this->view->content = "
<style>
li {
  list-style-type: none !important;
  padding: 0px !important;
}
.css-treeview ul {
  margin: 0px;
}
.grid-container {
  padding : 5px;
}
.css-treeview
{
    list-style: none;

}
.css-treeview li
{
    padding-left : 10px !important;

}


/* Align the label and provide a pointer cursor. */
.css-treeview label
{
    display: inline;
    vertical-align: middle;
    cursor: pointer;
}

/* Highlight selected nodes. */
.css-treeview label.selected
{
    background-color: #08C;
    color: white;
    padding: 2px;
}

/* Hide child nodes of an unchecked expander. */
.css-treeview input.expander ~ ul
{
    display: none;
}

/* Show child nodes of a checked expander. */
.css-treeview input.expander:checked ~ ul
{
    display: block;
}

/* Hide the expander checkbox. */
.css-treeview input.expander
{
    position: absolute;
    opacity: 0;
}


.css-treeview input.expander:disabled
{
    cursor: default;
}

/* Remove the margin from actual checkboxes. */
.css-treeview input.check
{
    margin: 0;
}


.css-treeview input.expander:disabled + span.expander::before
{
    content: \"\";
    padding-right: 20px;
}


.css-treeview input.expander:enabled + span.expander::before {
    background: url(\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAgCAYAAAAbifjMAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAadEVYdFNvZnR3YXJlAFBhaW50Lk5FVCB2My41LjEwMPRyoQAAAHtJREFUSEvtlEsKwCAMRHP/S6fYUhDsfETSQnEhrvIcM5NEZsbKWSpuD9cB4mTr70EFDeBAJEBBLACD2AAEeQ+AHLEUMDslQGWBAlRxbZSd17eCa9TrFso3LtxbSN29uqEHM8WwiQjy1Bc5jWq5UhtV8R9z4KaP5mAWcgD5xILE2Y3q1wAAAABJRU5ErkJggg==\");
    background-position: 0 0;
    content: \"\";
    padding-right: 20px;
}


.css-treeview input.expander:checked:enabled + span.expander::before
{
    background-position: 1px 16px;
}
</style>
        <div class=\"grid-container\">
  <ul class=\"css-treeview\">
    <li>
        <input type=\"checkbox\" class=\"expander\" checked>
        <span class=\"expander\"></span>
        <label>{$user->login} - {$user->name_f} {$user->name_l}</label>
        <ul class=\"css-treeview\">
          {$html}
        </ul>
    </li>
</ul>
</div>";
        $this->view->display('layout.phtml');
    }

}