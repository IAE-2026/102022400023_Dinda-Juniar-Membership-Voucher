{{-- Custom GraphiQL view — auto-injects X-IAE-KEY header for API authentication --}}
@php
use MLL\GraphiQL\GraphiQLAsset;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GraphiQL — Service C (Keanggotaan & Voucher)</title>
    <style>
        body {
            margin: 0;
            overflow: hidden; /* in Firefox */
        }

        #graphiql {
            height: 100dvh;
        }

        #graphiql-loading {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }

        .docExplorerWrap {
            /* Allow scrolling, see https://github.com/graphql/graphiql/issues/3098. */
            overflow: auto !important;
        }
    </style>
    <script src="{{ GraphiQLAsset::reactJS() }}"></script>
    <script src="{{ GraphiQLAsset::reactDOMJS() }}"></script>
    <link rel="stylesheet" href="{{ GraphiQLAsset::graphiQLCSS() }}"/>
    <link rel="stylesheet" href="{{ GraphiQLAsset::pluginExplorerCSS() }}"/>
    <link rel="shortcut icon" href="{{ GraphiQLAsset::favicon() }}"/>
</head>

<body>

<div id="graphiql">
    <div id="graphiql-loading">Loading…</div>
</div>

<script src="{{ GraphiQLAsset::graphiQLJS() }}"></script>
<script src="{{ GraphiQLAsset::pluginExplorerJS() }}"></script>
<script>
    const fetcher = GraphiQL.createFetcher({
        url: '{{ $url }}',
        subscriptionUrl: '{{ $subscriptionUrl }}',
        headers: {
            'X-IAE-KEY': '{{ env("IAE_API_KEYS") ? explode(",", env("IAE_API_KEYS"))[0] : "102022400023" }}',
        },
    });
    const explorer = GraphiQLPluginExplorer.explorerPlugin();

    const defaultQuery = `# Selamat datang di GraphiQL — Service C (Keanggotaan & Voucher)
# Header X-IAE-KEY sudah otomatis dikirim.
#
# Contoh query yang bisa dicoba:

# 1. Ambil semua member
query GetAllMemberships {
  memberships {
    id
    member_code
    name
    email
    phone
    membership_type
    status
    discount_percent
    registered_at
    expired_at
    usage_history {
      id
      transaction_id
      voucher_code
      used_at
    }
  }
}

# 2. Ambil member berdasarkan member_code
# query GetMember {
#   membership(member_code: "MBR-001") {
#     id
#     name
#     email
#     membership_type
#     status
#   }
# }

# 3. Ambil semua voucher
# query GetAllVouchers {
#   vouchers {
#     id
#     code
#     description
#     discount_type
#     discount_value
#     max_discount
#     is_used
#     valid_until
#   }
# }

# 4. Ambil voucher berdasarkan kode
# query GetVoucher {
#   voucher(code: "VCHR-001") {
#     id
#     code
#     description
#     discount_type
#     discount_value
#     is_used
#   }
# }
`;

    function GraphiQLWithExplorer() {
        return React.createElement(GraphiQL, {
            fetcher,
            defaultQuery,
            plugins: [
                explorer,
            ],
            // See https://github.com/graphql/graphiql/tree/main/packages/graphiql#props for available settings
        });
    }

    ReactDOM.render(
        React.createElement(GraphiQLWithExplorer),
        document.getElementById('graphiql'),
    );
</script>

</body>
</html>
