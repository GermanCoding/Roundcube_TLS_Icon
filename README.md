# Roundcube TLS Icon

Displays a small icon after the subject line that displays the (presumed) encryption state of received mails.
This plugin parses the "Received" header for the last hop and checks if TLS was used. This requires TLS logging in the receiving MTA.

In Postfix this can be enabled by setting `smtpd_tls_received_header = yes`. The regex used to parse the header has only been tested against Postfix.

Note that while this talks about "encryption", this does not imply security. An encrypted mail may still be insecure, mostly because mailservers generally use  "opportunistic TLS", where MITM attacks are possible.
This also only validates the last hop of an email - some emails may run through multiple hops and we don't know anything about the security of these.

Inspired by https://github.com/SS88UK/roundcube-easy-unsubscribe

![Example screenshot](tls_icon_example.png)