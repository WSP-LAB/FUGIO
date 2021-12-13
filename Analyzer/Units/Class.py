class Class():
    def __init__(self, name):
        self.name = name
        self.real_name = name
        self.parents = []
        self.implements = []
        self.traits = []
        self.prop_list = {}
        self.method_list = {}
        self.type = []
        self.code = ''
        self.namespace = ''
        self.uses = {}