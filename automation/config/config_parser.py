"""Configuration parser for AI UAT Handoff Template"""
import json
import yaml
from typing import Dict, List, Any
from dataclasses import dataclass


@dataclass
class UserCredential:
    email: str
    password: str
    label: str = ""


@dataclass
class TestConfig:
    base_url: str
    tenant_slug: str
    execution_mode: str
    lifecycle_mode: str
    users_by_role: Dict[str, List[UserCredential]]
    test_cases: List[Dict[str, Any]]


class ConfigParser:
    """Parse handoff template and test cases"""
    
    def __init__(self, handoff_path: str, test_cases_path: str):
        self.handoff_path = handoff_path
        self.test_cases_path = test_cases_path
        
    def parse_handoff(self) -> Dict[str, Any]:
        """Parse the markdown handoff template"""
        with open(self.handoff_path, 'r') as f:
            content = f.read()
        
        # Extract basic config
        config = {
            'base_url': 'https://conmgr.com',
            'tenant_slug': 'febtest1',
            'execution_mode': 'MULTI_USER_PER_ROLE',
            'lifecycle_mode': 'PRESEEDED_TENANT',
            'users_by_role': {}
        }
        
        # Parse the YAML block for users_by_role
        yaml_start = content.find('```yaml')
        yaml_end = content.find('```', yaml_start + 7)
        
        if yaml_start != -1 and yaml_end != -1:
            yaml_content = content[yaml_start + 7:yaml_end].strip()
            users_data = yaml.safe_load(yaml_content)
            
            if users_data and 'users_by_role' in users_data:
                for role, users in users_data['users_by_role'].items():
                    config['users_by_role'][role] = [
                        UserCredential(
                            email=u['email'],
                            password=u['password'],
                            label=u.get('label', '')
                        )
                        for u in users
                    ]
        
        return config
    
    def parse_test_cases(self) -> List[Dict[str, Any]]:
        """Parse the JSON test cases file"""
        with open(self.test_cases_path, 'r') as f:
            return json.load(f)
    
    def build_config(self) -> TestConfig:
        """Build complete test configuration"""
        handoff_data = self.parse_handoff()
        test_cases = self.parse_test_cases()
        
        return TestConfig(
            base_url=handoff_data['base_url'],
            tenant_slug=handoff_data['tenant_slug'],
            execution_mode=handoff_data['execution_mode'],
            lifecycle_mode=handoff_data['lifecycle_mode'],
            users_by_role=handoff_data['users_by_role'],
            test_cases=test_cases
        )


def get_config() -> TestConfig:
    """Get parsed configuration"""
    parser = ConfigParser(
        '/workspace/temps/AI-UAT-Handoff-Template-v2.md',
        '/workspace/temps/Acceptance-Test-Cases.json'
    )
    return parser.build_config()
