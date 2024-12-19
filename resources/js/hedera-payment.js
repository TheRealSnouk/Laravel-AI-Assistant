class HederaPayment {
    constructor() {
        this.hashconnect = new window.HashConnect();
        this.accountId = null;
        this.provider = null;
    }

    async connectHashPack() {
        try {
            const appMetadata = {
                name: "Laravel AI Assistant",
                description: "AI Coding Assistant Payment",
                icon: "/images/logo.png"
            };

            const initData = await this.hashconnect.init(appMetadata, "mainnet", false);
            
            // Save connection details
            const saveData = {
                topic: initData.topic,
                pairingString: initData.pairingString,
                privateKey: initData.privateKey
            };

            // Connect to HashPack
            const result = await this.hashconnect.connect();
            if (result.success) {
                this.accountId = result.accountIds[0];
                return {
                    success: true,
                    accountId: this.accountId
                };
            }

            throw new Error('Failed to connect to HashPack');

        } catch (error) {
            console.error('HashPack connection error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    async connectMetaMask() {
        try {
            if (!window.ethereum) {
                throw new Error('MetaMask not installed');
            }

            // Request account access
            const accounts = await window.ethereum.request({
                method: 'eth_requestAccounts'
            });

            // Switch to Hedera network
            await window.ethereum.request({
                method: 'wallet_addEthereumChain',
                params: [{
                    chainId: '0x127',
                    chainName: 'Hedera Mainnet',
                    nativeCurrency: {
                        name: 'HBAR',
                        symbol: 'HBAR',
                        decimals: 18
                    },
                    rpcUrls: ['https://mainnet.hashio.io/api'],
                    blockExplorerUrls: ['https://hashscan.io/mainnet']
                }]
            });

            this.provider = window.ethereum;
            return {
                success: true,
                account: accounts[0]
            };

        } catch (error) {
            console.error('MetaMask connection error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    async sendPayment(paymentDetails) {
        try {
            if (!paymentDetails.tokenId || !paymentDetails.amount || !paymentDetails.recipient) {
                throw new Error('Invalid payment details');
            }

            // If HashPack is connected
            if (this.hashconnect && this.accountId) {
                return await this.sendHashPackPayment(paymentDetails);
            }

            // If MetaMask is connected
            if (this.provider) {
                return await this.sendMetaMaskPayment(paymentDetails);
            }

            throw new Error('No wallet connected');

        } catch (error) {
            console.error('Payment error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    async sendHashPackPayment(paymentDetails) {
        try {
            const transaction = {
                type: "tokenTransfer",
                tokenId: paymentDetails.tokenId,
                accountId: this.accountId,
                amount: paymentDetails.amount,
                recipient: paymentDetails.recipient,
                memo: paymentDetails.memo
            };

            const result = await this.hashconnect.sendTransaction(transaction);
            return {
                success: true,
                transactionId: result.transactionId
            };

        } catch (error) {
            console.error('HashPack payment error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    async sendMetaMaskPayment(paymentDetails) {
        try {
            const tokenContract = new window.ethers.Contract(
                paymentDetails.tokenId,
                ['function transfer(address to, uint256 amount)'],
                new window.ethers.providers.Web3Provider(this.provider).getSigner()
            );

            const transaction = await tokenContract.transfer(
                paymentDetails.recipient,
                ethers.utils.parseUnits(paymentDetails.amount.toString(), 6) // USDT has 6 decimals
            );

            const receipt = await transaction.wait();
            return {
                success: true,
                transactionHash: receipt.transactionHash
            };

        } catch (error) {
            console.error('MetaMask payment error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
}

// Export for module bundlers
export default HederaPayment;
