# Wordpress plugin for token and user in RSS and Atom feed URLs

Plugin name: "Newsfeed Token With Username"

# Functionality

This adds a "token that includes user" parameter to newfeed links in Wordpress (RSS and Atom URLs).

Resulting feeds can look like the following:

- RSS 2: https://example.com/feed/?e3f5e05411c0e4db3810413bd330b1c4&t=username_a40058313e15a335874d80eca71edc0e
- Atom: https://example.com/feed/atom/?e3f5e05411c0e4db3810413bd330b1c4&t=username_a40058313e15a335874d80eca71edc0e

Where e3f5e05411c0e4db3810413bd330b1c4 is a site-level authentication token from another Plugin like Authentiactor at https://wordpress.org/plugins/authenticator/ and username_a40058313e15a335874d80eca71edc0e is the two-part token containing both the username and a random string.

Requests to a Wordpress RSS or Atom feed without a token will fail as 403 denied.

Also, Wordpress admins can look up a user's numeric ID under Users and then clear/reset that user's individual newsfeed token from the Wordpress web UI at Tools > Feed Token Admin.

# Installation

Plugins > Add New > Upload Plugin > select Zip file > Install Now > Activate Plugin

This plugin adds shortcodes to Wordpress named `user_feed_url` and `show_user_feed_url`.

So, when installing this Plugin Wordpress, modify the locations in the Wordpress template where Newsfeed URLs exist, to include these shortcodes, such as in the following lines, so that the correct per-user newsfeed links are provided to each user:

In our example, sidebar.php gets the following snippet:

```
  <?php if (is_user_logged_in()) : ?>
  <li><a href="<?php echo do_shortcode('[user_rss_feed_url]'); ?>">RSS 2.0 (personal URL)</a>
  <li><a href="<?php echo do_shortcode('[user_atom_feed_url]'); ?>">Atom (personal URL)</a>
  <?php endif; ?>
```

# Maintenance

To invalidate a token for a user, so that user will get a new token generated at the next appropriate time:

- Wordpress Admin > Tools > Tokens > enter user ID (from Users) > Clear if needed, to reset the token value on user's next page visit

# Background

There was an existing Wordpress plugin to "add tokens to newsfeeds for users to get unique private RSS feed links".

- That allowed users to subscribe to feeds in RSS readers, while still keeping the feeds private to members of the Wordpress site only, by giving each user a unique feed URL.

- That allow private-only access, with disabled accounts getting access revoked for that user.

However, that solution does not very well discourage leaking or sharing URLs for active users, since the tokens look like random strings and could "work anywhere" in an RSS reader.

So, this solution discourages link-sharing or link-leaking, by adding username to the user's "private newsfeed" token string.

It is acknowledged that "leaking username" to a "feed reader" or anywhere that URLs show up in cleartext may be undesireable as well in come contexts.  However, that is the tradeoff knowingly made in this solution.

Thus, it is suggested to use these URLs where username is not secret, e.g. in feed readers that remain local to one's own device, etc. (which, admittedly, might be difficult for some users to tell without inspecting traffic, but that is outside the scope of this plugin).

# Authors

Dan W and an LLM this plugin.

# Disclaimer

No guarantees are made.  Use at your own risk.

# Testing

Tested working in Wordpress 6.2.2.

# License

This Wordpress plugin "Newsfeed Token With Username" is released to the public domain.
