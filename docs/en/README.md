# logincas extension

This extension replaces the YesWiki login form by a centralised authentication on a
CAS server.

## Enabling the extension

The extension installs safely. As long as `cas_host` is not set, it stays inactive: a
message reports the missing configuration and the usual YesWiki login form keeps
working. The same happens when the CAS server does not answer or refuses the
authentication.

## Configuration

In `wakka.config.php`.

| Key | Required | Default | Purpose |
|---|---|---|---|
| `cas_host` | yes | empty | CAS server hostname, without scheme or path |
| `cas_port` | no | `443` | CAS server port |
| `cas_context` | no | `/cas` | path of the CAS service on that server |
| `cas_server_ca_cert_path` | no | empty | path to the server certificate chain |
| `cas_bazar_mapping` | no | `[]` | create a bazar entry on first login |

Minimal example:

```php
'cas_host' => 'cas.example.org',
```

Leaving `cas_server_ca_cert_path` empty disables validation of the CAS server
certificate. Handy while testing, and unacceptable in production: without that
validation the CAS protocol guarantees nothing.

## Creating a bazar entry on first login

`cas_bazar_mapping` maps the attributes returned by the CAS server onto the fields of
a bazar form. On first login the user is asked a consent question, then the entry is
created.

```php
'cas_bazar_mapping' => [
    [
        'id' => '1',
        'consent_question' => 'Do you agree to have your entry created on this wiki?',
        'fields' => [
            'bf_nom' => 'field_last_name',
            'bf_prenom' => 'field_first_name',
            'bf_mail' => 'mail',
            'bf_latitude' => 'field_lat_lon.latlon',
        ],
    ],
],
```

`id` is the bazar form identifier. Each entry in `fields` maps a bazar field name onto
a CAS attribute. The `attribute.key` notation reads one key out of a JSON-encoded
attribute.

All three keys `id`, `consent_question` and `fields` must be present, and `fields` must
not be empty, otherwise no entry is created.

## Usage

The extension replaces the `{{login}}` action. Nothing to change in your pages.

If `loginldap`, `loginsso` or another authentication extension is installed at the same
time, only one wins: YesWiki loads extensions in alphabetical order and the last one
loaded takes over. Install only one of them.

## Troubleshooting

| Message | Cause | What to do |
|---|---|---|
| The logincas extension is not configured | `cas_host` missing from `wakka.config.php` | set `cas_host` |
| The CAS server does not answer or is misconfigured | unreachable host, or wrong `cas_port` or `cas_context` | check those three keys and that the server is reachable |
| Authentication on the CAS server failed | the server answered without authenticating the user | check the account on the CAS server side |

In all three cases the usual login form is still displayed right below the message, so
local wiki accounts remain usable.

To trace the exchanges with the CAS server, add `&debug=1` to the login URL. The log is
written to `files/cas.log`. That file holds session tickets, so turn the option off and
delete the file once you are done.
