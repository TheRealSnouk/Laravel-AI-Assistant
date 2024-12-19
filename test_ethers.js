import { ethers } from 'ethers';

// Your Infura Project ID
const INFURA_PROJECT_ID = '4c7b35f5778344edbcf5063c06a9fdd7';

// Network configurations
const networks = {
    ethereum: {
        name: 'Ethereum Mainnet',
        url: `https://mainnet.infura.io/v3/${INFURA_PROJECT_ID}`,
        chainId: 1
    },
    polygon: {
        name: 'Polygon Mainnet',
        url: `https://polygon-mainnet.infura.io/v3/${INFURA_PROJECT_ID}`,
        chainId: 137
    },
    bsc: {
        name: 'BSC Mainnet',
        url: `https://bsc-mainnet.infura.io/v3/${INFURA_PROJECT_ID}`,
        chainId: 56
    }
};

// USDT Contract addresses
const usdtAddresses = {
    ethereum: '0xdAC17F958D2ee523a2206206994597C13D831ec7',
    polygon: '0xc2132D05D31c914a87C6611C10748AEb04B58e8F',
    bsc: '0x55d398326f99059fF775485246999027B3197955'
};

// ERC20 Interface for name()
const erc20Abi = [
    "function name() view returns (string)"
];

async function testNetwork(networkKey, networkConfig) {
    try {
        console.log(`\nTesting ${networkConfig.name}:`);
        
        // Create provider
        const provider = new ethers.JsonRpcProvider(networkConfig.url);
        
        try {
            // Test basic connection
            const blockNumber = await provider.getBlockNumber();
            console.log(`✅ Connected successfully! Current block: ${blockNumber}`);
            
            // Test USDT contract
            if (usdtAddresses[networkKey]) {
                const contract = new ethers.Contract(usdtAddresses[networkKey], erc20Abi, provider);
                try {
                    const name = await contract.name();
                    console.log(`✅ USDT Contract accessible! Token name: ${name}`);
                } catch (error) {
                    console.log(`❌ USDT Contract error: ${error.message}`);
                }
            }
        } catch (error) {
            console.log(`❌ RPC Error: ${error.message}`);
        }
        
    } catch (error) {
        console.log(`❌ Setup Error: ${error.message}`);
    }
}

async function runTests() {
    console.log('🔍 Testing Network Connections...\n');
    
    for (const [network, config] of Object.entries(networks)) {
        await testNetwork(network, config);
    }
}

// Run the tests
runTests().catch(console.error);
