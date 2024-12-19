import { ethers } from 'ethers';
import { SigningStargateClient } from '@cosmjs/stargate';

// ERC20 ABI for token transfers
const ERC20_ABI = [
    "function transfer(address to, uint256 amount) returns (bool)",
    "function balanceOf(address owner) view returns (uint256)",
    "function decimals() view returns (uint8)",
    "function symbol() view returns (string)",
];

export const walletHandlers = {
    // MetaMask (EVM) wallet handlers
    async connectMetaMask() {
        if (!window.ethereum) {
            throw new Error('MetaMask is not installed');
        }

        try {
            const accounts = await window.ethereum.request({ 
                method: 'eth_requestAccounts' 
            });
            return accounts[0];
        } catch (error) {
            console.error('Failed to connect MetaMask:', error);
            throw error;
        }
    },

    async switchEvmNetwork(chainId) {
        try {
            await window.ethereum.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId }],
            });
        } catch (error) {
            if (error.code === 4902) {
                // Chain not added, add it
                const networkData = this.getEvmNetworkData(chainId);
                if (networkData) {
                    await window.ethereum.request({
                        method: 'wallet_addEthereumChain',
                        params: [networkData],
                    });
                }
            } else {
                throw error;
            }
        }
    },

    getEvmNetworkData(chainId) {
        const networks = {
            '0x1': {
                chainId: '0x1',
                chainName: 'Ethereum Mainnet',
                nativeCurrency: {
                    name: 'Ether',
                    symbol: 'ETH',
                    decimals: 18
                },
                rpcUrls: ['https://mainnet.infura.io/v3/YOUR-PROJECT-ID'],
                blockExplorerUrls: ['https://etherscan.io']
            },
            '0x38': {
                chainId: '0x38',
                chainName: 'BNB Smart Chain',
                nativeCurrency: {
                    name: 'BNB',
                    symbol: 'BNB',
                    decimals: 18
                },
                rpcUrls: ['https://bsc-dataseed.binance.org'],
                blockExplorerUrls: ['https://bscscan.com']
            },
            '0x89': {
                chainId: '0x89',
                chainName: 'Polygon Mainnet',
                nativeCurrency: {
                    name: 'MATIC',
                    symbol: 'MATIC',
                    decimals: 18
                },
                rpcUrls: ['https://polygon-rpc.com'],
                blockExplorerUrls: ['https://polygonscan.com']
            }
        };
        return networks[chainId];
    },

    async sendEvmNativeToken(to, amount, chainId) {
        await this.switchEvmNetwork(chainId);
        
        const provider = new ethers.BrowserProvider(window.ethereum);
        const signer = await provider.getSigner();
        
        const tx = await signer.sendTransaction({
            to: to,
            value: ethers.parseEther(amount.toString())
        });
        
        return tx.hash;
    },

    async sendEvmToken(contractAddress, to, amount, chainId) {
        await this.switchEvmNetwork(chainId);
        
        const provider = new ethers.BrowserProvider(window.ethereum);
        const signer = await provider.getSigner();
        const contract = new ethers.Contract(contractAddress, ERC20_ABI, signer);
        
        const decimals = await contract.decimals();
        const amountInSmallestUnit = ethers.parseUnits(amount.toString(), decimals);
        
        const tx = await contract.transfer(to, amountInSmallestUnit);
        return tx.hash;
    },

    // Keplr (Cosmos) wallet handlers
    async connectKeplr() {
        if (!window.keplr) {
            throw new Error('Keplr wallet is not installed');
        }

        try {
            await window.keplr.enable('cosmoshub-4');
            const offlineSigner = window.keplr.getOfflineSigner('cosmoshub-4');
            const accounts = await offlineSigner.getAccounts();
            return accounts[0].address;
        } catch (error) {
            console.error('Failed to connect Keplr:', error);
            throw error;
        }
    },

    async sendAtom(recipientAddress, amount) {
        if (!window.keplr) {
            throw new Error('Keplr wallet is not installed');
        }

        try {
            await window.keplr.enable('cosmoshub-4');
            const offlineSigner = window.keplr.getOfflineSigner('cosmoshub-4');
            const client = await SigningStargateClient.connectWithSigner(
                'https://rpc-cosmoshub.keplr.app',
                offlineSigner
            );

            const sender = (await offlineSigner.getAccounts())[0].address;
            const amountInUatom = Math.floor(amount * 1000000); // Convert to uatom (6 decimals)

            const tx = await client.sendTokens(
                sender,
                recipientAddress,
                [{ denom: 'uatom', amount: amountInUatom.toString() }],
                {
                    amount: [{ denom: 'uatom', amount: '5000' }],
                    gas: '200000',
                }
            );

            return tx.transactionHash;
        } catch (error) {
            console.error('Failed to send ATOM:', error);
            throw error;
        }
    }
};

// Initialize wallet handlers
document.addEventListener('alpine:init', () => {
    Alpine.data('cryptoWallet', () => ({
        async pay(paymentDetails) {
            try {
                let txHash;
                const notification = (message, type = 'info') => {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { message, type }
                    }));
                };

                if (paymentDetails.wallet === 'metamask') {
                    notification('Connecting to MetaMask...');
                    await walletHandlers.connectMetaMask();

                    if (paymentDetails.token === 'native') {
                        notification('Sending native tokens...');
                        txHash = await walletHandlers.sendEvmNativeToken(
                            paymentDetails.address,
                            paymentDetails.amount,
                            paymentDetails.chainId
                        );
                    } else {
                        notification('Sending tokens...');
                        txHash = await walletHandlers.sendEvmToken(
                            paymentDetails.contractAddress,
                            paymentDetails.address,
                            paymentDetails.amount,
                            paymentDetails.chainId
                        );
                    }
                } else if (paymentDetails.wallet === 'keplr') {
                    notification('Connecting to Keplr...');
                    await walletHandlers.connectKeplr();

                    notification('Sending ATOM...');
                    txHash = await walletHandlers.sendAtom(
                        paymentDetails.address,
                        paymentDetails.amount
                    );
                }

                // Verify payment on backend
                const response = await fetch('/api/verify-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        transaction_hash: txHash,
                        wallet: paymentDetails.wallet,
                        network: paymentDetails.network,
                        amount: paymentDetails.amount,
                        currency: paymentDetails.currency
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    notification('Payment successful!', 'success');
                    // Redirect or update UI as needed
                    window.location.href = '/dashboard';
                } else {
                    notification('Payment verification failed. Please contact support.', 'error');
                }
            } catch (error) {
                console.error('Payment failed:', error);
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: error.message || 'Payment failed. Please try again.',
                        type: 'error'
                    }
                }));
            }
        }
    }));
});
