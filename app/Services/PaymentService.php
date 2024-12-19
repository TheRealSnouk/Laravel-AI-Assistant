<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    // Hedera account ID and keys
    const HEDERA_ACCOUNT_ID = env('HEDERA_ACCOUNT_ID');
    const HEDERA_PRIVATE_KEY = env('HEDERA_PRIVATE_KEY');
    
    // Contract addresses
    const ETH_USDT_CONTRACT = env('ETH_USDT_CONTRACT');
    const BSC_USDT_CONTRACT = env('BSC_USDT_CONTRACT');
    const POLYGON_USDT_CONTRACT = env('POLYGON_USDT_CONTRACT');
    const ATOM_USDT_CONTRACT = env('ATOM_USDT_CONTRACT');
    
    // Wallet addresses
    const MERCHANT_ETH_ADDRESS = env('MERCHANT_ETH_ADDRESS');
    const MERCHANT_COSMOS_ADDRESS = env('MERCHANT_COSMOS_ADDRESS');
    
    // Subscription prices in USD
    const PRICES = [
        'basic' => 10,
        'pro' => 30
    ];

    protected $prices = [];

    public function __construct()
    {
        $this->updatePrices();
    }

    public function updatePrices()
    {
        try {
            $response = Http::get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => 'ethereum,binancecoin,matic-network,cosmos,hedera-hashgraph,tether',
                'vs_currencies' => 'usd'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->prices = [
                    'eth' => $data['ethereum']['usd'],
                    'bnb' => $data['binancecoin']['usd'],
                    'matic' => $data['matic-network']['usd'],
                    'atom' => $data['cosmos']['usd'],
                    'hbar' => $data['hedera-hashgraph']['usd'],
                    'usdt' => $data['tether']['usd']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch crypto prices', ['error' => $e->getMessage()]);
        }
    }

    public function generatePaymentDetails($tier)
    {
        if (!isset(self::PRICES[$tier])) {
            throw new \InvalidArgumentException('Invalid subscription tier');
        }

        $priceUSD = self::PRICES[$tier];
        
        return [
            'tier' => $tier,
            'price_usd' => $priceUSD,
            'crypto' => [
                'evm' => [
                    'networks' => [
                        'ethereum' => [
                            'name' => 'Ethereum',
                            'chainId' => '0x1',
                            'rpcUrl' => 'https://mainnet.infura.io/v3/' . env('INFURA_PROJECT_ID'),
                            'blockExplorer' => 'https://etherscan.io',
                            'nativeCurrency' => [
                                'symbol' => 'ETH',
                                'decimals' => 18,
                                'amount' => round($priceUSD / $this->prices['eth'], 6),
                                'address' => self::MERCHANT_ETH_ADDRESS
                            ],
                            'usdt' => [
                                'symbol' => 'USDT',
                                'decimals' => 6,
                                'amount' => $priceUSD,
                                'contract' => self::ETH_USDT_CONTRACT,
                                'address' => self::MERCHANT_ETH_ADDRESS
                            ]
                        ],
                        'bsc' => [
                            'name' => 'BNB Smart Chain',
                            'chainId' => '0x38',
                            'rpcUrl' => 'https://bsc-dataseed.binance.org',
                            'blockExplorer' => 'https://bscscan.com',
                            'nativeCurrency' => [
                                'symbol' => 'BNB',
                                'decimals' => 18,
                                'amount' => round($priceUSD / $this->prices['bnb'], 6),
                                'address' => self::MERCHANT_ETH_ADDRESS
                            ],
                            'usdt' => [
                                'symbol' => 'USDT',
                                'decimals' => 18,
                                'amount' => $priceUSD,
                                'contract' => self::BSC_USDT_CONTRACT,
                                'address' => self::MERCHANT_ETH_ADDRESS
                            ]
                        ],
                        'polygon' => [
                            'name' => 'Polygon',
                            'chainId' => '0x89',
                            'rpcUrl' => 'https://polygon-rpc.com',
                            'blockExplorer' => 'https://polygonscan.com',
                            'nativeCurrency' => [
                                'symbol' => 'MATIC',
                                'decimals' => 18,
                                'amount' => round($priceUSD / $this->prices['matic'], 6),
                                'address' => self::MERCHANT_ETH_ADDRESS
                            ],
                            'usdt' => [
                                'symbol' => 'USDT',
                                'decimals' => 6,
                                'amount' => $priceUSD,
                                'contract' => self::POLYGON_USDT_CONTRACT,
                                'address' => self::MERCHANT_ETH_ADDRESS
                            ]
                        ]
                    ]
                ],
                'cosmos' => [
                    'networks' => [
                        'cosmos-hub' => [
                            'name' => 'Cosmos Hub',
                            'chainId' => 'cosmoshub-4',
                            'rpcUrl' => 'https://rpc-cosmoshub.keplr.app',
                            'restUrl' => 'https://lcd-cosmoshub.keplr.app',
                            'blockExplorer' => 'https://www.mintscan.io/cosmos',
                            'nativeCurrency' => [
                                'symbol' => 'ATOM',
                                'decimals' => 6,
                                'amount' => round($priceUSD / $this->prices['atom'], 6),
                                'address' => self::MERCHANT_COSMOS_ADDRESS
                            ],
                            'usdt' => [
                                'symbol' => 'USDT',
                                'decimals' => 6,
                                'amount' => $priceUSD,
                                'contract' => self::ATOM_USDT_CONTRACT,
                                'address' => self::MERCHANT_COSMOS_ADDRESS
                            ]
                        ]
                    ]
                ],
                'hedera' => [
                    'networks' => [
                        'mainnet' => [
                            'name' => 'Hedera',
                            'nativeCurrency' => [
                                'symbol' => 'HBAR',
                                'decimals' => 8,
                                'amount' => round($priceUSD / $this->prices['hbar'], 6),
                                'account_id' => self::HEDERA_ACCOUNT_ID
                            ],
                            'usdt' => [
                                'symbol' => 'USDT',
                                'decimals' => 6,
                                'amount' => $priceUSD,
                                'token_id' => self::HEDERA_USDT_TOKEN,
                                'account_id' => self::HEDERA_ACCOUNT_ID
                            ]
                        ]
                    ]
                ]
            ],
            'traditional' => [
                'paypal' => [
                    'amount' => $priceUSD,
                    'currency' => 'USD'
                ],
                'card' => [
                    'amount' => $priceUSD,
                    'currency' => 'USD',
                    'supported_cards' => ['visa', 'mastercard', 'amex']
                ]
            ]
        ];
    }

    public function verifyEvmPayment($network, $transactionHash, $expectedAmount, $currency = 'native')
    {
        try {
            $baseUrl = match($network) {
                'ethereum' => "https://api.etherscan.io/api",
                'bsc' => "https://api.bscscan.com/api",
                'polygon' => "https://api.polygonscan.com/api",
                default => throw new \Exception('Unsupported network')
            };

            $apiKey = match($network) {
                'ethereum' => env('ETHERSCAN_API_KEY'),
                'bsc' => env('BSCSCAN_API_KEY'),
                'polygon' => env('POLYGONSCAN_API_KEY'),
                default => throw new \Exception('API key not found')
            };

            $response = Http::get($baseUrl, [
                'module' => 'transaction',
                'action' => 'gettxreceiptstatus',
                'txhash' => $transactionHash,
                'apikey' => $apiKey
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to verify transaction');
            }

            $data = $response->json();
            
            if ($data['status'] !== '1' || $data['message'] !== 'OK') {
                return false;
            }

            // Verify transaction details
            $txResponse = Http::get($baseUrl, [
                'module' => 'proxy',
                'action' => 'eth_getTransactionByHash',
                'txhash' => $transactionHash,
                'apikey' => $apiKey
            ]);

            $tx = $txResponse->json()['result'];
            
            // Convert hex value to decimal and from wei to native token
            $value = hexdec($tx['value']) / pow(10, 18);
            
            return $value >= $expectedAmount;

        } catch (\Exception $e) {
            Log::error('EVM payment verification failed', [
                'error' => $e->getMessage(),
                'transaction' => $transactionHash
            ]);
            return false;
        }
    }

    public function verifyCosmosPayment($transactionHash, $expectedAmount, $currency = 'native')
    {
        try {
            $response = Http::get("https://lcd-cosmoshub.keplr.app/cosmos/tx/v1beta1/txs/{$transactionHash}");
            
            if (!$response->successful()) {
                throw new \Exception('Failed to verify transaction');
            }

            $data = $response->json();
            
            // Check if transaction was successful
            if ($data['tx_response']['code'] !== 0) {
                return false;
            }

            // Verify amount
            foreach ($data['tx']['body']['messages'] as $msg) {
                if ($msg['@type'] === '/cosmos.bank.v1beta1.MsgSend') {
                    $amount = $msg['amount'][0]['amount'] / pow(10, 6); // Convert from uatom to ATOM
                    return $amount >= $expectedAmount;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Cosmos payment verification failed', [
                'error' => $e->getMessage(),
                'transaction' => $transactionHash
            ]);
            return false;
        }
    }

    public function verifyHederaPayment($transactionId, $expectedAmount, $type = 'hbar')
    {
        try {
            $response = Http::get("https://mainnet-public.mirrornode.hedera.com/api/v1/transactions/{$transactionId}");
            
            if (!$response->successful()) {
                throw new \Exception('Failed to verify Hedera transaction');
            }

            $data = $response->json();
            
            if ($data['result'] !== 'SUCCESS') {
                return false;
            }

            if ($type === 'hbar') {
                $amount = $data['transfers'][0]['amount'] / pow(10, 8); // Convert from tinybars
                return $amount >= $expectedAmount;
            } else {
                foreach ($data['token_transfers'] as $transfer) {
                    if ($transfer['token_id'] === self::HEDERA_USDT_TOKEN) {
                        $amount = $transfer['amount'] / pow(10, 6); // USDT has 6 decimals
                        return $amount >= $expectedAmount;
                    }
                }
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Hedera payment verification failed', [
                'error' => $e->getMessage(),
                'transaction' => $transactionId
            ]);
            return false;
        }
    }
}
