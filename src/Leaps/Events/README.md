##事件

##基本用法

Leaps 的 Event 类提供一个简单的观察者实现，允许您在应用程序里订阅与监听事件。

订阅事件

```php
$this->event->listen('auth.login', function($user)
{
    $user->last_login = new DateTime;

    $user->save();
});
```
###触发事件

$response = Event::fire('auth.login', array($user));
fire 方法返回的是一个响应（response）数组，可以用来控制应用程序的后续行为。

###订阅有优先顺序的事件

您也可以在订阅事件的时候指定一个优先顺序。 有较高优先权的监听者会先被执行，当监听者有一样的优先权时将会依照订阅的顺序执行.

```php
$this->event->listen('auth.login', 'LoginHandler', 10);
$this->event->listen('auth.login', 'OtherHandler', 5);
```
###停止继续传递事件

您有时候会希望停止继续传递事件到其他监听者。 您可以通过从监听者回传 false 来做到这件事：

```php
$this->event->listen('auth.login', function($event)
{
    // Handle the event...

    return false;
});
```
###在哪里注册事件

现在您知道怎么注册事件了，但是您或许会想知道要在 哪里 注册它们。 不要担心，这是一个常见的问题。 不幸地，这是一个很难回答的问题，因为您几乎可以在任何地方注册事件！ 但是，这里有一些提示。 一样的，您可以在您的其中一个 `start` 文件注册事件，就像其他大部份的启动代码，例如： `app/start/global.php`。

如果您的 start 文件变得越来越拥挤，您可以建立一个分离的 `app/events.php` 文件，并从 start 文件引入它。 这是个简单的解决方案，它保持您的事件注册与剩余的启动代码干净地分离。

如果您喜欢基于类的方法，您可以在 服务提供者 注册您的事件。 因为这些方法中没有一个是绝对正确的方案，基于您的应用程序大小选择一个让您感到舒服的方法。


##通配符监听者

###注册通配符事件监听者

当注册事件监听者，您可以使用星号(*) 指定通配符监听者：

```php
$this->event->listen('foo.*', function($param)
{
    // Handle the event...
});
```
这个监听者将会处理所有 foo. 开头的事件。

您可以使用 Event::firing 方法准确的判定是什么事件被触发：

```php
$this->event->listen('foo.*', function($param)
{
    if (Event::firing() == 'foo.bar')
    {
        //
    }
});
```

##使用类作为监听者

在一些案例中，您或许会希望使用类取代闭包来处理事件。 类事件监听者将会被 Leaps container 处理，提供依赖注入的全部功能给您的监听者。

###注册类监听者
    $this->event->listen('auth.login', 'LoginHandler');
###定义事件监听者类
LoginHandler 类默认将会调用 handle 方法：

```php
class LoginHandler {

    public function handle($data)
    {
        //
    }

}
```

###指定被订阅的方法

如果您不希望使用默认的 handle 方法, 您可以指定应该被订阅的方法：

    $this->event->listen('auth.login', 'LoginHandler@onLogin');

##事件队列

###注册事件队列

使用 queue 和 flush 方法， 您可以把事件加到队列等待触发，但是不立即触发它：

    $this->event->queue('foo', array($user));
您可以执行 "flusher" 并触发全部的事件队列，使用 flush 方法：

    $this->event->flush('foo');

##事件订阅者

###定义事件订阅者

事件订阅者是个可以从类自身里面订阅多个事件的类。 订阅者应该定义 subscribe 方法，它将会被传递到事件配送器实例：

```php
class UserEventHandler {

    /**
     * Handle user login events.
     */
    public function onUserLogin($event)
    {
        //
    }

    /**
     * Handle user logout events.
     */
    public function onUserLogout($event)
    {
        //
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Leaps\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('auth.login', 'UserEventHandler@onUserLogin');

        $events->listen('auth.logout', 'UserEventHandler@onUserLogout');
    }

}
```
###注册事件订阅者

当订阅者被定义时，它或许会使用 Event 类注册。

    $subscriber = new UserEventHandler;

    $this->event->subscribe($subscriber);
您也可以使用 Leaps container 去处理您的订阅者。 简单地传递订阅者的名字给 subscribe 方法就可以做到：

    $this->event->subscribe('UserEventHandler');